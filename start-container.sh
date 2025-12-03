#!/bin/sh

# Exit immediately if a command exits with a non-zero status
set -e

echo "Running migrations..."
# Use --force to avoid "Do you really wish to run this command?" prompts in production
php artisan migrate --force

echo "Starting FrankenPHP..."
php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=8000
