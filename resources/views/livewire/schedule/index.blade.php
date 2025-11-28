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
 */
$courses = computed(function () {
    $startOfWeek = $this->selectedWeek->copy()->startOfWeek();
    $endOfWeek = $this->selectedWeek->copy()->endOfWeek();

    return \App\Models\Event::query()
        ->courses()
        ->betweenDates($startOfWeek, $endOfWeek)
        ->forSubject($this->selectedSubject)
        ->forTeacher($this->selectedTeacher)
        ->forCourseType($this->selectedCourseType)
        ->orderBy('start_time')
        ->get();
});

/**
 * Computed property pour obtenir les devoirs de la semaine
 */
$homeworks = computed(function () {
    $startOfWeek = $this->selectedWeek->copy()->startOfWeek();
    $endOfWeek = $this->selectedWeek->copy()->endOfWeek();

    return \App\Models\Event::query()
        ->homework()
        ->betweenDates($startOfWeek, $endOfWeek)
        ->forSubject($this->selectedSubject)
        ->orderBy('due_date')
        ->get();
});

/**
 * Computed property pour obtenir les matières disponibles
 */
$subjects = computed(function () {
    return \App\Models\Event::query()
        ->courses()
        ->whereNotNull('subject')
        ->distinct()
        ->pluck('subject')
        ->sort()
        ->values();
});

/**
 * Computed property pour obtenir les enseignants disponibles
 */
$teachers = computed(function () {
    return \App\Models\Event::query()
        ->courses()
        ->whereNotNull('teacher')
        ->distinct()
        ->pluck('teacher')
        ->sort()
        ->values();
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

/**
 * Action pour créer un devoir à partir d'un créneau horaire cliqué
 */
$createHomeworkAt = function ($date, $hour) {
    // Créer une date avec l'heure sélectionnée
    $dueDate = \Carbon\Carbon::parse($date)->setTime($hour, 0);

    // Chercher s'il y a un cours à cette heure pour pré-remplir la matière et l'enseignant
    $dayStart = $dueDate->copy()->startOfHour();
    $dayEnd = $dueDate->copy()->endOfHour();

    $courseAtTime = \App\Models\Event::query()
        ->courses()
        ->where(function($q) use ($dayStart, $dayEnd) {
            $q->whereBetween('start_time', [$dayStart, $dayEnd])
              ->orWhereBetween('end_time', [$dayStart, $dayEnd])
              ->orWhere(function($q) use ($dayStart, $dayEnd) {
                  $q->where('start_time', '<=', $dayStart)
                    ->where('end_time', '>=', $dayEnd);
              });
        })
        ->first();

    // Construire les paramètres de la requête
    $params = ['due_date' => $dueDate->format('Y-m-d\TH:i')];


    if ($courseAtTime) {
        if ($courseAtTime->title) {
            $params['subject'] = $courseAtTime->title;
        }
        if ($courseAtTime->teacher) {
            $params['teacher'] = $courseAtTime->teacher;
        }
    }

    // Rediriger vers la page de création avec les données pré-remplies
    $this->redirect(route('homeworks.create', $params), navigate: true);
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
            <a
                href="{{ route('schedule.export.ics', [
                    'start_date' => $selectedWeek->format('Y-m-d'),
                    'end_date' => $selectedWeek->copy()->endOfWeek()->format('Y-m-d'),
                    'subject' => $selectedSubject,
                    'teacher' => $selectedTeacher,
                    'course_type' => $selectedCourseType,
                ]) }}"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
            >
                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" />
                    <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
                </svg>
                Exporter (ICS)
            </a>
        </div>
    </div>

    {{-- Astuce pour ajouter des devoirs --}}
    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950/30">
        <div class="flex items-start gap-3">
            <svg class="size-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                <strong>Astuce :</strong> Cliquez sur un créneau horaire vide dans le calendrier pour ajouter rapidement un devoir à cette date et heure.
            </flux:text>
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
                        @foreach($this->subjects as $subject)
                            <option value="{{ $subject }}">{{ $subject }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            {{-- Filtre par enseignant --}}
            <div>
                <flux:field>
                    <flux:label>Enseignant</flux:label>
                    <flux:select wire:model.live="selectedTeacher" placeholder="Tous les enseignants">
                        @foreach($this->teachers as $teacher)
                            <option value="{{ $teacher }}">{{ $teacher }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            {{-- Filtre par type de cours --}}
            <div>
                <flux:field>
                    <flux:label>Type de cours</flux:label>
                    <flux:select wire:model.live="selectedCourseType" placeholder="Tous les types">
                        <option value="CM">Cours Magistral (CM)</option>
                        <option value="TD">Travaux Dirigés (TD)</option>
                        <option value="TP">Travaux Pratiques (TP)</option>
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
                <div class="grid grid-cols-6">
                    {{-- Boucle sur les heures de 8h à 18h --}}
                    @for($hour = 8; $hour <= 18; $hour++)
                        {{-- Colonne des heures --}}
                        <div class="border-r border-t border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900">
                            <flux:text class="text-xs font-medium text-zinc-600 dark:text-zinc-400">
                                {{ sprintf('%02d:00', $hour) }}
                            </flux:text>
                        </div>

                        {{-- Colonnes des jours --}}
                        @foreach($this->weekDays as $day)
                            @php
                                // Récupérer les cours pour ce jour et cette heure
                                $dayStart = $day->copy()->setTime($hour, 0, 0);
                                $dayEnd = $day->copy()->setTime($hour, 59, 59);

                                $coursesForSlot = $this->courses->filter(function($course) use ($dayStart, $dayEnd) {
                                    return $course->start_time->between($dayStart, $dayEnd) ||
                                           $course->end_time->between($dayStart, $dayEnd) ||
                                           ($course->start_time->lessThan($dayStart) && $course->end_time->greaterThan($dayEnd));
                                });

                                // Récupérer les devoirs à rendre ce jour (affichés uniquement dans le premier créneau horaire)
                                $homeworksForDay = $hour === 8 ? $this->homeworks->filter(function($homework) use ($day) {
                                    return $homework->due_date->isSameDay($day);
                                }) : collect();
                            @endphp

                            <div
                                class="relative min-h-[60px] border-t border-zinc-200 p-2 dark:border-zinc-700 {{ $day->isToday() ? 'bg-zinc-50 dark:bg-zinc-900/50' : '' }} cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-950/30 transition-colors group"
                                wire:click="createHomeworkAt('{{ $day->format('Y-m-d') }}', {{ $hour }})"
                                title="Cliquer pour ajouter un devoir à {{ $day->format('d/m') }} à {{ $hour }}h"
                            >
                                {{-- Indicateur visuel au survol --}}
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-0">
                                    <svg class="size-8 text-blue-400 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>

                                {{-- Afficher les cours --}}
                                @foreach($coursesForSlot as $course)
                                    @if($course->start_time->hour === $hour)
                                        @php
                                            $courseTypeColors = [
                                                'CM' => 'bg-blue-100 border-blue-300 text-blue-900 dark:bg-blue-950 dark:border-blue-800 dark:text-blue-200',
                                                'TD' => 'bg-green-100 border-green-300 text-green-900 dark:bg-green-950 dark:border-green-800 dark:text-green-200',
                                                'TP' => 'bg-purple-100 border-purple-300 text-purple-900 dark:bg-purple-950 dark:border-purple-800 dark:text-purple-200',
                                            ];
                                            $colorClass = $courseTypeColors[$course->course_type] ?? 'bg-zinc-100 border-zinc-300 text-zinc-900 dark:bg-zinc-950 dark:border-zinc-800 dark:text-zinc-200';
                                        @endphp

                                        <div class="mb-1 rounded border {{ $colorClass }} p-2 text-xs relative z-10" wire:click.stop>
                                            <div class="flex items-center justify-between">
                                                <flux:text class="font-semibold">{{ $course->title }}</flux:text>
                                                <flux:badge size="sm" color="zinc">{{ $course->course_type }}</flux:badge>
                                            </div>
                                            <flux:text class="mt-1 text-xs">
                                                {{ $course->start_time->format('H:i') }} - {{ $course->end_time->format('H:i') }}
                                            </flux:text>
                                            @if($course->room)
                                                <flux:text class="mt-0.5 text-xs opacity-75">
                                                    📍 {{ $course->room }}
                                                </flux:text>
                                            @endif
                                            @if($course->teacher)
                                                <flux:text class="mt-0.5 text-xs opacity-75">
                                                    👤 {{ $course->teacher }}
                                                </flux:text>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach

                                {{-- Afficher les devoirs à rendre ce jour (dans le premier créneau uniquement) --}}
                                @foreach($homeworksForDay as $homework)
                                    @php
                                        $priorityColors = [
                                            'high' => 'bg-red-100 border-red-400 text-red-900 dark:bg-red-950 dark:border-red-700 dark:text-red-200',
                                            'medium' => 'bg-yellow-100 border-yellow-400 text-yellow-900 dark:bg-yellow-950 dark:border-yellow-700 dark:text-yellow-200',
                                            'low' => 'bg-zinc-100 border-zinc-400 text-zinc-900 dark:bg-zinc-950 dark:border-zinc-700 dark:text-zinc-200',
                                        ];
                                        $homeworkColorClass = $priorityColors[$homework->priority] ?? $priorityColors['low'];
                                        $isOverdue = $homework->due_date < now() && !$homework->completed;
                                    @endphp

                                    <a href="{{ route('homeworks.edit', $homework) }}" class="mb-1 block rounded border {{ $homeworkColorClass }} p-2 text-xs hover:opacity-80 transition-opacity relative z-10" wire:navigate wire:click.stop>
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-1">
                                                <flux:text class="font-semibold">📝 {{ $homework->title }}</flux:text>
                                                @if($homework->completed)
                                                    <span class="text-green-600 dark:text-green-400">✓</span>
                                                @endif
                                            </div>
                                            @if($isOverdue)
                                                <flux:badge size="sm" color="red">Retard</flux:badge>
                                            @else
                                                <flux:badge size="sm" color="zinc">Devoir</flux:badge>
                                            @endif
                                        </div>
                                        <flux:text class="mt-1 text-xs">
                                            À rendre : {{ $homework->due_date->format('H:i') }}
                                        </flux:text>
                                        @if($homework->subject)
                                            <flux:text class="mt-0.5 text-xs opacity-75">
                                                📚 {{ $homework->subject }}
                                            </flux:text>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        @endforeach
                    @endfor
                </div>
            </div>
        </div>
    </div>

    {{-- Section des devoirs de la semaine --}}
    @if($this->homeworks->count() > 0)
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="size-6 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <flux:heading size="sm" class="text-lg font-medium">
                        Devoirs de la semaine
                    </flux:heading>
                </div>
                <flux:button href="{{ route('homeworks.index') }}" variant="ghost" size="sm" wire:navigate>
                    Voir tous les devoirs
                </flux:button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($this->homeworks as $homework)
                    @php
                        $priorityColors = [
                            'high' => 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-950/30',
                            'medium' => 'border-yellow-300 bg-yellow-50 dark:border-yellow-700 dark:bg-yellow-950/30',
                            'low' => 'border-zinc-300 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/30',
                        ];
                        $homeworkBorderClass = $priorityColors[$homework->priority] ?? $priorityColors['low'];
                        $isOverdue = $homework->due_date < now() && !$homework->completed;
                    @endphp

                    <a href="{{ route('homeworks.edit', $homework) }}" class="block rounded-lg border p-4 transition-colors hover:border-zinc-400 dark:hover:border-zinc-600 {{ $homeworkBorderClass }}" wire:navigate>
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <flux:text class="font-semibold text-sm {{ $homework->completed ? 'line-through text-zinc-500' : '' }}">
                                {{ $homework->title }}
                            </flux:text>
                            @if($homework->completed)
                                <span class="text-green-600 dark:text-green-400 text-lg">✓</span>
                            @endif
                        </div>

                        <div class="space-y-1 text-xs text-zinc-600 dark:text-zinc-400">
                            <div class="flex items-center gap-1">
                                <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>{{ $homework->due_date->format('D d/m à H:i') }}</span>
                                @if($isOverdue)
                                    <flux:badge size="sm" color="red" class="ml-1">Retard</flux:badge>
                                @endif
                            </div>

                            @if($homework->subject)
                                <div class="flex items-center gap-1">
                                    <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <span>{{ $homework->subject }}</span>
                                </div>
                            @endif

                            <div class="flex items-center gap-1">
                                @if($homework->priority === 'high')
                                    <flux:badge size="sm" color="red">Priorité élevée</flux:badge>
                                @elseif($homework->priority === 'medium')
                                    <flux:badge size="sm" color="yellow">Priorité moyenne</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">Priorité faible</flux:badge>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

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
                    Actions disponibles
                </flux:text>
                <div class="space-y-2">
                    <flux:text class="text-sm text-green-600 dark:text-green-400">
                        ✓ Exporter au format iCal (ICS)
                    </flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        • Exporter en PDF
                    </flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        • S'abonner aux notifications
                    </flux:text>
                </div>
            </div>
        </div>
    </div>
</div>

