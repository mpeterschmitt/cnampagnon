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

                    @auth
                        <flux:button
                            variant="outline"
                            :href="route('schedule.index')"
                            wire:navigate
                        >
                            Voir mon emploi du temps
                        </flux:button>
                    @endauth

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
    </div>
</div>

