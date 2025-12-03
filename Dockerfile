# ==============================================================================
# Global Arguments
# ==============================================================================
ARG PHP_VERSION=8.5
ARG NODE_VERSION=24
ARG APP_HOME=/app

# ==============================================================================
# Stage 1: Base (Common dependencies)
# ==============================================================================
FROM dunglas/frankenphp:php${PHP_VERSION}-alpine AS base

# Install system dependencies required for runtime (Python, SQLite libs)
# We install python3 here so it exists in the final image
RUN apk add --no-cache \
    python3 \
    libstdc++ \
    sqlite-libs \
    icu-libs \
    acl \
    bash

# install-php-extensions (provided by FrankenPHP base)
# Add any other extensions you need here (e.g., redis, gd)
RUN install-php-extensions \
    pcntl \
    bcmath \
    intl \
    zip \
    opcache \
    pdo_sqlite

##==============================================================================
# Stage 2: Composer (PHP Dependencies)
# ==============================================================================
FROM composer:2 AS composer_builder
WORKDIR /app
COPY composer.json composer.lock ./
# Install deps with optimization
RUN composer install \
    --no-dev \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist

# ==============================================================================
# Stage 3: Node (Frontend Assets)
# ==============================================================================
FROM node:${NODE_VERSION}-alpine AS node_builder
WORKDIR /app
COPY package.json package-lock.json* ./

# needs vendor for fluxui
COPY --from=composer_builder /app/vendor ./vendor

RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
ARG ASSET_URL
ARG APP_URL
ENV ASSET_URL=$ASSET_URL
ENV APP_URL=$APP_URL

# Build assets (creates public/build)
RUN npm run build

# ==============================================================================
# Stage 3: Python (UV & Dependencies)
# ==============================================================================
FROM base AS python_builder
WORKDIR /app

# Get uv binary from official image
COPY --from=ghcr.io/astral-sh/uv:latest /uv /bin/uv

# Copy python requirements
COPY pyproject.toml uv.lock* requirements.txt* ./

# Create virtual environment and install dependencies
# We use --system-site-packages=false to ensure isolation,
# but we are building ON the base Alpine image to ensure binary compatibility
ENV UV_COMPILE_BYTECODE=1
ENV UV_LINK_MODE=copy

# If using pyproject.toml/uv.lock
RUN uv venv .venv && \
    uv sync --frozen --no-install-project

# OR if using requirements.txt (uncomment below if not using pyproject.toml)
# RUN uv venv .venv && \
#     uv pip install -r requirements.txt

# ==============================================================================
# Stage 5: Final Runtime (Rootless & Optimized)
# ==============================================================================
FROM base AS final

ARG APP_HOME
ENV APP_HOME=${APP_HOME}
# Binding to 8000 allows rootless execution (ports < 1024 require root)
ENV SERVER_NAME=":8000"

# Production PHP Optimization
ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/conf.d"
ENV OPCACHE_VALIDATE_TIMESTAMPS=0
ENV OPCACHE_MAX_ACCELERATED_FILES=20000
ENV OPCACHE_MEMORY_CONSUMPTION=256

# Setup Rootless User
# We create a user named 'laravel' with a high UID to avoid conflicts
RUN addgroup -g 1000 laravel && \
    adduser -D -u 1000 -G laravel -h ${APP_HOME} laravel && \
    chown -R laravel:laravel ${APP_HOME} \
    && chmod 755 ${APP_HOME}

WORKDIR ${APP_HOME}

# 1. Copy PHP Vendors
COPY --from=composer_builder --chown=laravel:laravel /app/vendor ./vendor
# get composer binary
COPY --from=composer_builder /usr/bin/composer /usr/bin/composer
# 2. Copy Node Assets
COPY --from=node_builder --chown=laravel:laravel /app/public/build ./public/build
# 3. Copy Python Virtual Environment
COPY --from=python_builder --chown=laravel:laravel /app/.venv ./.venv
# 4. Copy Application Code
COPY --chown=laravel:laravel . .

# Final Directory Permissions
# Ensure storage and bootstrap cache are writable
# Ensure database directory exists and is writable for SQLite
RUN mkdir -p storage/framework/{sessions,views,cache} bootstrap/cache db && \
    touch db/database.sqlite && \
    chown -R laravel:laravel storage bootstrap/cache db && \
    chmod -R 775 storage bootstrap/cache db && \
    mkdir -p /data/caddy /config/caddy && \
    chown -R laravel:laravel /data /config

# Optimize Composer Autoloader (now that we have all files)
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# Copy the script
COPY start-container.sh /usr/local/bin/start-container.sh

# Make it executable
RUN chmod +x /usr/local/bin/start-container.sh

# Switch to Rootless User
USER laravel

# Activate Python venv in path so 'python' command uses the venv
ENV PATH="${APP_HOME}/.venv/bin:$PATH"

# Entrypoint to handle runtime caching
ENTRYPOINT ["start-container.sh"]
