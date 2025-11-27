<?php

use function Livewire\Volt\computed;
use function Livewire\Volt\layout;
use function Livewire\Volt\state;

/**
 * Composant principal pour l'emploi du temps
 *
 * Ce composant gère l'affichage et la manipulation de l'emploi du temps hebdomadaire.
 * Il permet de visualiser les cours, filtrer par différents critères, et gérer
 * les modifications de dernière minute.
 */
layout('components.layouts.app');

// État du composant
state([
    'selectedWeek' => now()->startOfWeek(),  // Semaine actuellement affichée
    'selectedSubject' => null,                // Filtre: matière sélectionnée
    'selectedTeacher' => null,                // Filtre: enseignant sélectionné
    'selectedCourseType' => null,             // Filtre: type de cours (CM, TD, TP)
    'viewMode' => 'week',                     // Mode d'affichage: 'week' ou 'day'
]);

/**
 * Computed property pour obtenir les jours de la semaine affichée
 * Format: Array de Carbon instances pour chaque jour de la semaine
 */
$weekDays = computed(function () {
    $days = [];
    $start = $this->selectedWeek;

    for ($i = 0; $i < 5; $i++) { // Lundi à Vendredi
        $days[] = $start->copy()->addDays($i);
    }

    return $days;
});

/**
 * Computed property pour obtenir les cours de la semaine
 * TODO: Remplacer par des données depuis la base de données ou une API
 */
$courses = computed(function () {
    // Placeholder: données de démonstration
    // À remplacer par: Course::query()->whereBetween('start_time', [...])->get()
    return collect([]);
});

/**
 * Action pour naviguer vers la semaine précédente
 */
$previousWeek = function () {
    $this->selectedWeek = $this->selectedWeek->copy()->subWeek();
};

/**
 * Action pour naviguer vers la semaine suivante
 */
$nextWeek = function () {
    $this->selectedWeek = $this->selectedWeek->copy()->addWeek();
};

/**
 * Action pour revenir à la semaine actuelle
 */
$currentWeek = function () {
    $this->selectedWeek = now()->startOfWeek();
};

/**
 * Action pour réinitialiser tous les filtres
 */
$clearFilters = function () {
    $this->selectedSubject = null;
    $this->selectedTeacher = null;
    $this->selectedCourseType = null;
};

?>

<div class="space-y-6">
    {{-- En-tête de la page --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="text-2xl font-semibold">
                Emploi du Temps
            </flux:heading>
            <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                Planning hebdomadaire des cours et activités
            </flux:text>
        </div>

        {{-- Actions rapides --}}
        <div class="flex gap-2">
            <flux:button variant="outline" icon="arrow-path" wire:click="currentWeek">
                Aujourd'hui
            </flux:button>
        </div>
    </div>

    {{-- Section des filtres --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading size="sm" class="text-lg font-medium">
                Filtres
            </flux:heading>
            @if($selectedSubject || $selectedTeacher || $selectedCourseType)
                <flux:button variant="ghost" size="sm" wire:click="clearFilters">
                    Réinitialiser
                </flux:button>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            {{-- Filtre par matière --}}
            <div>
                <flux:field>
                    <flux:label>Matière</flux:label>
                    <flux:select wire:model.live="selectedSubject" placeholder="Toutes les matières">
                        {{-- TODO: Charger dynamiquement depuis la base de données --}}
                        {{-- <option value="math">Mathématiques</option> --}}
                        {{-- <option value="physics">Physique</option> --}}
                    </flux:select>
                </flux:field>
            </div>

            {{-- Filtre par enseignant --}}
            <div>
                <flux:field>
                    <flux:label>Enseignant</flux:label>
                    <flux:select wire:model.live="selectedTeacher" placeholder="Tous les enseignants">
                        {{-- TODO: Charger dynamiquement depuis la base de données --}}
                        {{-- <option value="1">M. Dupont</option> --}}
                        {{-- <option value="2">Mme Martin</option> --}}
                    </flux:select>
                </flux:field>
            </div>

            {{-- Filtre par type de cours --}}
            <div>
                <flux:field>
                    <flux:label>Type de cours</flux:label>
                    <flux:select wire:model.live="selectedCourseType" placeholder="Tous les types">
                        <option value="cm">Cours Magistral (CM)</option>
                        <option value="td">Travaux Dirigés (TD)</option>
                        <option value="tp">Travaux Pratiques (TP)</option>
                    </flux:select>
                </flux:field>
            </div>
        </div>
    </div>

    {{-- Navigation de semaine --}}
    <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:button variant="ghost" icon="chevron-left" wire:click="previousWeek">
            Semaine précédente
        </flux:button>

        <div class="text-center">
            <flux:heading size="sm" class="text-lg font-medium">
                Semaine du {{ $selectedWeek->format('d/m/Y') }}
            </flux:heading>
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                S{{ $selectedWeek->format('W') }} - {{ $selectedWeek->format('Y') }}
            </flux:text>
        </div>

        <flux:button variant="ghost" icon-trailing="chevron-right" wire:click="nextWeek">
            Semaine suivante
        </flux:button>
    </div>

    {{-- Grille de l'emploi du temps --}}
    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        {{-- En-têtes des jours --}}
        <div class="grid grid-cols-6 border-b border-zinc-200 dark:border-zinc-700">
            {{-- Colonne des heures --}}
            <div class="border-r border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                    Heures
                </flux:text>
            </div>

            {{-- Colonnes des jours de la semaine --}}
            @foreach($this->weekDays as $day)
                <div class="p-4 text-center {{ $day->isToday() ? 'bg-blue-50 dark:bg-blue-950' : '' }}">
                    <flux:text class="block text-sm font-medium">
                        {{ $day->isoFormat('dddd') }}
                    </flux:text>
                    <flux:text class="mt-1 block text-lg font-semibold {{ $day->isToday() ? 'text-blue-600 dark:text-blue-400' : '' }}">
                        {{ $day->format('d') }}
                    </flux:text>
                </div>
            @endforeach
        </div>

        {{-- Grille horaire --}}
        <div class="overflow-x-auto">
            <div class="min-w-full">
                {{--
                    TODO: Implémenter la grille horaire avec les créneaux
                    - Créer une boucle pour les heures (8h00 - 18h00)
                    - Pour chaque heure, afficher les créneaux disponibles
                    - Positionner les cours en fonction de leur horaire
                    - Gérer les cours qui se chevauchent
                    - Ajouter la possibilité de cliquer sur un cours pour voir les détails
                --}}
                <div class="grid grid-cols-6">
                    {{-- Exemple de ligne horaire (à répéter pour chaque créneau) --}}
                    @for($hour = 8; $hour <= 18; $hour++)
                        {{-- Colonne des heures --}}
                        <div class="border-r border-t border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900">
                            <flux:text class="text-xs font-medium text-zinc-600 dark:text-zinc-400">
                                {{ sprintf('%02d:00', $hour) }}
                            </flux:text>
                        </div>

                        {{-- Colonnes des jours --}}
                        @foreach($this->weekDays as $day)
                            <div class="min-h-[60px] border-t border-zinc-200 p-2 dark:border-zinc-700 {{ $day->isToday() ? 'bg-zinc-50 dark:bg-zinc-900/50' : '' }}">
                                {{--
                                    TODO: Afficher les cours pour ce créneau
                                    - Vérifier si un cours existe à cette heure pour ce jour
                                    - Afficher les détails du cours (matière, salle, enseignant)
                                    - Gérer les différentes durées de cours
                                    - Ajouter des indicateurs visuels (couleurs par matière)
                                    - Afficher les changements de dernière minute avec un badge
                                --}}
                            </div>
                        @endforeach
                    @endfor
                </div>
            </div>
        </div>
    </div>

    {{-- Section des changements de dernière minute --}}
    <div class="rounded-lg border border-orange-200 bg-orange-50 p-6 dark:border-orange-800 dark:bg-orange-950/30">
        <div class="mb-4 flex items-start gap-3">
            <flux:icon.exclamation-triangle class="size-6 text-orange-600 dark:text-orange-400" />
            <div class="flex-1">
                <flux:heading size="sm" class="text-lg font-medium text-orange-900 dark:text-orange-200">
                    Changements de dernière minute
                </flux:heading>
                <flux:text class="mt-1 text-sm text-orange-700 dark:text-orange-300">
                    Les modifications récentes de l'emploi du temps apparaîtront ici
                </flux:text>
            </div>
        </div>

        {{--
            TODO: Afficher la liste des changements récents
            - Cours annulés
            - Changements de salle
            - Changements d'horaire
            - Remplacements d'enseignants
            - Grouper par date et heure
        --}}
        <div class="mt-4 text-center">
            <flux:text class="text-sm text-orange-600 dark:text-orange-400">
                Aucun changement récent
            </flux:text>
        </div>
    </div>

    {{-- Section légende --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="sm" class="mb-4 text-lg font-medium">
            Légende
        </flux:heading>

        <div class="grid gap-4 sm:grid-cols-3">
            {{-- Types de cours --}}
            <div>
                <flux:text class="mb-2 text-sm font-medium text-zinc-600 dark:text-zinc-400">
                    Types de cours
                </flux:text>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full bg-blue-500"></div>
                        <flux:text class="text-sm">Cours Magistral (CM)</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full bg-green-500"></div>
                        <flux:text class="text-sm">Travaux Dirigés (TD)</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full bg-purple-500"></div>
                        <flux:text class="text-sm">Travaux Pratiques (TP)</flux:text>
                    </div>
                </div>
            </div>

            {{-- Statuts --}}
            <div>
                <flux:text class="mb-2 text-sm font-medium text-zinc-600 dark:text-zinc-400">
                    Statuts
                </flux:text>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <flux:icon.check-circle class="size-4 text-green-600" />
                        <flux:text class="text-sm">Cours confirmé</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon.exclamation-triangle class="size-4 text-orange-600" />
                        <flux:text class="text-sm">Changement récent</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon.x-circle class="size-4 text-red-600" />
                        <flux:text class="text-sm">Cours annulé</flux:text>
                    </div>
                </div>
            </div>

            {{-- Actions disponibles (pour plus tard) --}}
            <div>
                <flux:text class="mb-2 text-sm font-medium text-zinc-600 dark:text-zinc-400">
                    Actions futures
                </flux:text>
                <div class="space-y-2">
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        • Exporter le planning (PDF/iCal)
                    </flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        • S'abonner aux notifications
                    </flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        • Synchroniser avec calendrier
                    </flux:text>
                </div>
            </div>
        </div>
    </div>
</div>

