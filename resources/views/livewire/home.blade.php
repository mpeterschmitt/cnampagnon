<?php

use Livewire\Volt\Component;
use function Livewire\Volt\{layout, state};

layout('components.layouts.landing');

state(['sharepointUrl' => 'https://cfaiformation-my.sharepoint.com/:f:/g/personal/mpeterschmitt1_cfai-formation_fr/IgCQi66vpvSuQYqwjkeLu-y6AQrTZlwBRuAxePu5FaaKPb0?e=eNsjIQ']);

?>

<div>
    <div class="relative isolate overflow-hidden">
        <!-- Hero Section -->
        <div class="mx-auto max-w-7xl px-4 py-24 sm:px-6 sm:py-32 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <!-- Badge -->
                <div class="mb-8 flex justify-center">
                    <flux:badge variant="outline" class="rounded-full">
                        🎓 Plateforme CNAM Promo
                    </flux:badge>
                </div>

                <!-- Title -->
                <flux:heading size="xl" class="mb-6 text-4xl font-semibold tracking-tight sm:text-5xl lg:text-6xl">
                    Bienvenue sur la plateforme de promotion
                </flux:heading>

                <!-- Subtitle -->
                <flux:text class="mx-auto mb-10 max-w-xl text-lg text-zinc-600 dark:text-zinc-400">
                    Votre espace dédié pour collaborer, partager et rester connecté avec votre promotion d'ingénieurs du CNAM.
                </flux:text>

                <!-- CTA Buttons -->
                <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <flux:button
                        variant="primary"
                        :href="$sharepointUrl"
                        target="_blank"
                        icon="arrow-right"
                    >
                        Accéder à SharePoint
                    </flux:button>

                    @guest
                        <flux:button
                            variant="outline"
                            :href="route('register')"
                            wire:navigate
                        >
                            S'inscrire
                        </flux:button>
                    @endguest
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center mb-12">
                <flux:heading size="lg" class="mb-4 text-3xl font-semibold">
                    Fonctionnalités à venir
                </flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    La plateforme évoluera pour répondre aux besoins de la promotion
                </flux:text>
            </div>

            <div class="grid gap-8 md:grid-cols-3">
                <!-- Feature 1 -->
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                        <flux:icon.users class="size-6" />
                    </div>
                    <flux:heading size="sm" class="mb-2 text-xl font-semibold">
                        Réseau
                    </flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        Restez en contact avec vos camarades et partagez vos expériences professionnelles.
                    </flux:text>
                </div>

                <!-- Feature 2 -->
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                        <flux:icon.folder class="size-6" />
                    </div>
                    <flux:heading size="sm" class="mb-2 text-xl font-semibold">
                        Ressources
                    </flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        Accédez aux documents, projets et ressources partagées de la promotion.
                    </flux:text>
                </div>

                <!-- Feature 3 -->
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                        <flux:icon.calendar class="size-6" />
                    </div>
                    <flux:heading size="sm" class="mb-2 text-xl font-semibold">
                        Événements
                    </flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                        Soyez informé des événements, rencontres et opportunités de networking.
                    </flux:text>
                </div>
            </div>
        </div>
    </div>
</div>

