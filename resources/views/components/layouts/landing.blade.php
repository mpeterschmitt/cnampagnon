<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-900 antialiased">
        <!-- Header -->
        <header class="border-b border-zinc-200 dark:border-zinc-700">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center">
                        <a href="/" class="flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                            <x-app-logo />
                        </a>
                    </div>

                    <nav class="flex items-center gap-4">
                        @auth
                            <flux:button :href="route('dashboard')" variant="ghost" wire:navigate>
                                {{ __('Dashboard') }}
                            </flux:button>
                        @else
                            @if (Route::has('login'))
                                <flux:button :href="route('login')" variant="ghost" wire:navigate>
                                    {{ __('Se connecter') }}
                                </flux:button>
                            @endif

                            @if (Route::has('register'))
                                <flux:button :href="route('register')" variant="primary" wire:navigate>
                                    {{ __('S\'inscrire') }}
                                </flux:button>
                            @endif
                        @endauth
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main>
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="border-t border-zinc-200 dark:border-zinc-700">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                        &copy; {{ date('Y') }} CNAM Promo. {{ __('Tous droits réservés.') }}
                    </div>
                    <div class="flex items-center gap-4 text-sm">
                        <a href="#" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                            {{ __('À propos') }}
                        </a>
                        <a href="#" class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                            {{ __('Contact') }}
                        </a>
                    </div>
                </div>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>

