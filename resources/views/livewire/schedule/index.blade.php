<?php

use Carbon\Carbon;

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
    'selectedMonth' => now()->startOfMonth(), // Mois actuellement affiché
    'selectedDay' => now()->startOfDay(),     // Jour actuellement affiché
    'selectedSubject' => null,                // Filtre: matière sélectionnée
    'selectedTeacher' => null,                // Filtre: enseignant sélectionné
    'selectedCourseType' => null,             // Filtre: type de cours (CM, TD, TP)
    'viewMode' => 'week',                     // Mode d'affichage: 'week', 'month', ou 'day'
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
 * Computed property pour obtenir les jours du mois affiché
 * Format: Array de Carbon instances pour chaque jour du mois (avec padding)
 */
$monthDays = computed(function () {
    $days = [];
    $start = $this->selectedMonth->copy()->startOfMonth();
    $end = $this->selectedMonth->copy()->endOfMonth();

    // Ajouter les jours du mois précédent pour compléter la première semaine
    $firstDayOfWeek = $start->dayOfWeekIso; // 1 = lundi, 7 = dimanche
    for ($i = 1; $i < $firstDayOfWeek; $i++) {
        $days[] = $start->copy()->subDays($firstDayOfWeek - $i);
    }

    // Ajouter tous les jours du mois
    $current = $start->copy();
    while ($current <= $end) {
        $days[] = $current->copy();
        $current->addDay();
    }

    // Ajouter les jours du mois suivant pour compléter la dernière semaine
    $lastDayOfWeek = $end->dayOfWeekIso;
    if ($lastDayOfWeek < 7) {
        for ($i = 1; $i <= 7 - $lastDayOfWeek; $i++) {
            $days[] = $end->copy()->addDays($i);
        }
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
 * Computed property pour obtenir les cours du mois
 */
$monthCourses = computed(function () {
    $startOfMonth = $this->selectedMonth->copy()->startOfMonth()->startOfDay();
    $endOfMonth = $this->selectedMonth->copy()->endOfMonth()->endOfDay();

    return \App\Models\Event::query()
        ->courses()
        ->betweenDates($startOfMonth, $endOfMonth)
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
 * Computed property pour obtenir les devoirs du mois
 */
$monthHomeworks = computed(function () {
    $startOfMonth = $this->selectedMonth->copy()->startOfMonth()->startOfDay();
    $endOfMonth = $this->selectedMonth->copy()->endOfMonth()->endOfDay();

    return \App\Models\Event::query()
        ->homework()
        ->betweenDates($startOfMonth, $endOfMonth)
        ->forSubject($this->selectedSubject)
        ->orderBy('due_date')
        ->get();
});

/**
 * Computed property pour obtenir les examens de la semaine
 */
$exams = computed(function () {
    $startOfWeek = $this->selectedWeek->copy()->startOfWeek();
    $endOfWeek = $this->selectedWeek->copy()->endOfWeek();

    return \App\Models\Event::query()
        ->exams()
        ->betweenDates($startOfWeek, $endOfWeek)
        ->forSubject($this->selectedSubject)
        ->orderBy('start_time')
        ->get();
});

/**
 * Computed property pour obtenir les examens du mois
 */
$monthExams = computed(function () {
    $startOfMonth = $this->selectedMonth->copy()->startOfMonth()->startOfDay();
    $endOfMonth = $this->selectedMonth->copy()->endOfMonth()->endOfDay();

    return \App\Models\Event::query()
        ->exams()
        ->betweenDates($startOfMonth, $endOfMonth)
        ->forSubject($this->selectedSubject)
        ->orderBy('start_time')
        ->get();
});

/**
 * Computed property pour obtenir les cours d'un jour
 */
$dayCourses = computed(function () {
    $startOfDay = $this->selectedDay->copy()->startOfDay();
    $endOfDay = $this->selectedDay->copy()->endOfDay();

    return \App\Models\Event::query()
        ->courses()
        ->betweenDates($startOfDay, $endOfDay)
        ->forSubject($this->selectedSubject)
        ->forTeacher($this->selectedTeacher)
        ->forCourseType($this->selectedCourseType)
        ->orderBy('start_time')
        ->get();
});

/**
 * Computed property pour obtenir les devoirs d'un jour
 */
$dayHomeworks = computed(function () {
    $startOfDay = $this->selectedDay->copy()->startOfDay();
    $endOfDay = $this->selectedDay->copy()->endOfDay();

    return \App\Models\Event::query()
        ->homework()
        ->betweenDates($startOfDay, $endOfDay)
        ->forSubject($this->selectedSubject)
        ->orderBy('due_date')
        ->get();
});

/**
 * Computed property pour obtenir les examens d'un jour
 */
$dayExams = computed(function () {
    $startOfDay = $this->selectedDay->copy()->startOfDay();
    $endOfDay = $this->selectedDay->copy()->endOfDay();

    return \App\Models\Event::query()
        ->exams()
        ->betweenDates($startOfDay, $endOfDay)
        ->forSubject($this->selectedSubject)
        ->orderBy('start_time')
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
 * Action pour naviguer vers le mois précédent
 */
$previousMonth = function () {
    $this->selectedMonth = $this->selectedMonth->copy()->subMonth();
};

/**
 * Action pour naviguer vers le mois suivant
 */
$nextMonth = function () {
    $this->selectedMonth = $this->selectedMonth->copy()->addMonth();
};

/**
 * Action pour revenir au mois actuel
 */
$currentMonth = function () {
    $this->selectedMonth = now()->startOfMonth();
};

/**
 * Action pour naviguer vers le jour précédent
 */
$previousDay = function () {
    $this->selectedDay = $this->selectedDay->copy()->subDay();
};

/**
 * Action pour naviguer vers le jour suivant
 */
$nextDay = function () {
    $this->selectedDay = $this->selectedDay->copy()->addDay();
};

/**
 * Action pour revenir au jour actuel
 */
$currentDay = function () {
    $this->selectedDay = now()->startOfDay();
};

/**
 * Action pour basculer entre les modes d'affichage
 */
$toggleViewMode = function ($mode) {
    $this->viewMode = $mode;
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
    $dueDate = Carbon::parse($date)->setTime($hour, 0);

    // Chercher s'il y a un cours à cette heure pour pré-remplir la matière et l'enseignant
    $dayStart = $dueDate->copy()->startOfHour();
    $dayEnd = $dueDate->copy()->endOfHour();

    $courseAtTime = \App\Models\Event::query()
        ->courses()
        ->where(function ($q) use ($dayStart, $dayEnd) {
            $q->whereBetween('start_time', [$dayStart, $dayEnd])
                ->orWhereBetween('end_time', [$dayStart, $dayEnd])
                ->orWhere(function ($q) use ($dayStart, $dayEnd) {
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
                Planning {{ $viewMode === 'month' ? 'mensuel' : ($viewMode === 'day' ? 'journalier' : 'hebdomadaire') }} des cours et activités
            </flux:text>
        </div>

        {{-- Actions rapides --}}
        <div class="flex flex-wrap gap-2">
            {{-- Sélecteur de vue --}}
            <div class="flex rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <button
                    wire:click="toggleViewMode('day')"
                    class="px-3 py-2 text-sm font-medium transition-colors {{ $viewMode === 'day' ? 'bg-blue-600 text-white dark:bg-blue-500' : 'bg-white text-zinc-700 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' }}"
                >
                    Jour
                </button>
                <button
                    wire:click="toggleViewMode('week')"
                    class="px-3 py-2 text-sm font-medium border-l border-zinc-200 dark:border-zinc-700 transition-colors {{ $viewMode === 'week' ? 'bg-blue-600 text-white dark:bg-blue-500' : 'bg-white text-zinc-700 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' }}"
                >
                    Semaine
                </button>
                <button
                    wire:click="toggleViewMode('month')"
                    class="px-3 py-2 text-sm font-medium border-l border-zinc-200 dark:border-zinc-700 transition-colors {{ $viewMode === 'month' ? 'bg-blue-600 text-white dark:bg-blue-500' : 'bg-white text-zinc-700 hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' }}"
                >
                    Mois
                </button>
            </div>

            <flux:button variant="outline" icon="arrow-path" wire:click="{{ $viewMode === 'month' ? 'currentMonth' : ($viewMode === 'day' ? 'currentDay' : 'currentWeek') }}">
                Aujourd'hui
            </flux:button>
            <a
                href="{{ route('schedule.export.ics') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
            >
                <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path
                        d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z"/>
                    <path
                        d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"/>
                </svg>
                Exporter (ICS)
            </a>
        </div>
    </div>

    {{-- Astuce pour ajouter des devoirs --}}
    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950/30">
        <div class="flex items-start gap-3">
            <svg class="size-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <flux:text class="text-sm text-blue-700 dark:text-blue-300">
                <strong>Astuce :</strong> Cliquez sur un créneau horaire vide dans le calendrier pour ajouter rapidement
                un devoir à cette date et heure.
            </flux:text>
        </div>
    </div>

    {{-- Navigation de semaine/mois --}}
    <div
        class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        @if($viewMode === 'month')
            <flux:button variant="ghost" icon="chevron-left" wire:click="previousMonth">
                Mois précédent
            </flux:button>

            <div class="text-center">
                <flux:heading size="sm" class="text-lg font-medium">
                    {{ $selectedMonth->isoFormat('MMMM YYYY') }}
                </flux:heading>
            </div>

            <flux:button variant="ghost" icon-trailing="chevron-right" wire:click="nextMonth">
                Mois suivant
            </flux:button>
        @elseif($viewMode === 'day')
            <flux:button variant="ghost" icon="chevron-left" wire:click="previousDay">
                Jour précédent
            </flux:button>

            <div class="text-center">
                <flux:heading size="sm" class="text-lg font-medium">
                    {{ $selectedDay->isoFormat('dddd D MMMM YYYY') }}
                </flux:heading>
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $selectedDay->isToday() ? "Aujourd'hui" : ($selectedDay->isTomorrow() ? "Demain" : ($selectedDay->isYesterday() ? "Hier" : "")) }}
                </flux:text>
            </div>

            <flux:button variant="ghost" icon-trailing="chevron-right" wire:click="nextDay">
                Jour suivant
            </flux:button>
        @else
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
        @endif
    </div>

    {{-- Grille de l'emploi du temps --}}
    @if($viewMode === 'week')
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
                    <flux:text
                        class="mt-1 block text-lg font-semibold {{ $day->isToday() ? 'text-blue-600 dark:text-blue-400' : '' }}">
                        {{ $day->format('d') }}
                    </flux:text>
                </div>
            @endforeach
        </div>

        {{-- Grille horaire --}}
        <div class="overflow-x-auto">
            <div class="min-w-full">
                {{-- Conteneur avec position relative pour les événements absolus --}}
                <div class="relative">
                    {{-- Grille de fond avec les heures --}}
                    <div class="grid grid-cols-6">
                        @for($hour = 8; $hour <= 18; $hour++)
                            {{-- Colonne des heures --}}
                            <div
                                class="border-r border-t border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900 h-20">
                                <flux:text class="text-xs font-medium text-zinc-600 dark:text-zinc-400">
                                    {{ sprintf('%02d:00', $hour) }}
                                </flux:text>
                            </div>

                            {{-- Colonnes des jours (cellules vides cliquables) --}}
                            @foreach($this->weekDays as $dayIndex => $day)
                                <div
                                    class="relative border-t border-zinc-200 p-2 dark:border-zinc-700 {{ $day->isToday() ? 'bg-zinc-50 dark:bg-zinc-900/50' : '' }} cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-950/30 transition-colors group h-20"
                                    wire:click="createHomeworkAt('{{ $day->format('Y-m-d') }}', {{ $hour }})"
                                    title="Cliquer pour ajouter un devoir à {{ $day->format('d/m') }} à {{ $hour }}h"
                                >
                                    {{-- Indicateur visuel au survol --}}
                                    <div
                                        class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-0">
                                        <svg class="size-8 text-blue-400 dark:text-blue-500" fill="none"
                                             stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </div>
                                </div>
                            @endforeach
                        @endfor
                    </div>

                    {{-- Overlay des événements avec position absolue --}}
                    @foreach($this->weekDays as $dayIndex => $day)
                        @php
                            $dayStart = $day->copy()->setTime(8, 0, 0);
                            $dayEnd = $day->copy()->setTime(18, 59, 59);

                            // Récupérer tous les événements de ce jour
                            $eventsForDay = $this->courses->filter(function($course) use ($day) {
                                return $course->start_time->isSameDay($day);
                            });

                            $homeworksForDay = $this->homeworks->filter(function($homework) use ($day) {
                                return $homework->due_date->isSameDay($day);
                            });

                            $examsForDay = $this->exams->filter(function($exam) use ($day) {
                                return $exam->start_time->isSameDay($day);
                            });

                            // Combiner et trier par heure de début
                            $allEvents = $eventsForDay->merge($homeworksForDay)->merge($examsForDay)->sortBy(function($event) {
                                return $event->type === 'homework' ? $event->due_date : $event->start_time;
                            })->values();

                            // Calculer les chevauchements pour positionner les événements côte à côte
                            $eventColumns = [];
                            $processedEvents = [];

                            foreach($allEvents as $index => $event) {
                                $eventStart = $event->type === 'homework' ? $event->due_date : $event->start_time;
                                $eventEnd = $event->type === 'homework' ? $event->due_date->copy()->addMinutes(30) : $event->end_time;

                                // Pour les devoirs après 18h, les afficher en bas du créneau 18h
                                if ($event->type === 'homework' && $eventStart->hour >= 18) {
                                    $eventStart = $eventStart->copy()->setTime(18, 30, 0);
                                    $eventEnd = $eventStart->copy()->addMinutes(30);
                                }

                                // Trouver tous les événements qui se chevauchent avec celui-ci
                                $overlappingEvents = [];
                                foreach($allEvents as $otherIndex => $otherEvent) {
                                    if ($index === $otherIndex) {
                                        continue;
                                    }

                                    $otherStart = $otherEvent->type === 'homework' ? $otherEvent->due_date : $otherEvent->start_time;
                                    $otherEnd = $otherEvent->type === 'homework' ? $otherEvent->due_date->copy()->addMinutes(30) : $otherEvent->end_time;

                                    // Appliquer la même règle pour les devoirs après 18h
                                    if ($otherEvent->type === 'homework' && $otherStart->hour >= 18) {
                                        $otherStart = $otherStart->copy()->setTime(18, 30, 0);
                                        $otherEnd = $otherStart->copy()->addMinutes(30);
                                    }

                                    if ($eventStart < $otherEnd && $eventEnd > $otherStart) {
                                        $overlappingEvents[] = $otherIndex;
                                    }
                                }

                                // Calculer le nombre total de colonnes nécessaires pour ce groupe d'événements
                                $totalColumns = count($overlappingEvents) + 1;

                                // Trouver la première colonne disponible parmi les événements qui se chevauchent
                                $column = 0;
                                foreach($eventColumns as $col => $colEvents) {
                                    $hasOverlap = false;
                                    foreach($colEvents as $existingEvent) {
                                        $existingStart = $existingEvent['start'];
                                        $existingEnd = $existingEvent['end'];

                                        if ($eventStart < $existingEnd && $eventEnd > $existingStart) {
                                            $hasOverlap = true;
                                            break;
                                        }
                                    }

                                    if (!$hasOverlap) {
                                        break;
                                    }
                                    $column++;
                                }

                                if (!isset($eventColumns[$column])) {
                                    $eventColumns[$column] = [];
                                }

                                $eventColumns[$column][] = [
                                    'event' => $event,
                                    'start' => $eventStart,
                                    'end' => $eventEnd,
                                    'column' => $column,
                                    'totalColumns' => $totalColumns
                                ];

                                $processedEvents[$index] = [
                                    'start' => $eventStart,
                                    'end' => $eventEnd,
                                    'totalColumns' => $totalColumns,
                                ];
                            }
                        @endphp

                        {{-- Afficher les événements --}}
                        @foreach($eventColumns as $column => $columnEvents)
                            @foreach($columnEvents as $eventData)
                                @php
                                    $event = $eventData['event'];
                                    $column = $eventData['column'];
                                    $totalColumns = $eventData['totalColumns'];

                                    $isHomework = $event->type === 'homework';
                                    $eventStart = $isHomework ? $event->due_date : $event->start_time;
                                    $eventEnd = $isHomework ? $event->due_date->copy()->addMinutes(30) : $event->end_time;

                                    // Pour les devoirs après 18h, les afficher en bas du créneau 18h
                                    if ($isHomework && $eventStart->hour >= 18) {
                                        $eventStart = $eventStart->copy()->setTime(18, 30, 0);
                                        $eventEnd = $eventStart->copy()->addMinutes(30);
                                    }

                                    // Calculer la position et la hauteur
                                    $startHour = $eventStart->hour + ($eventStart->minute / 60);
                                    $endHour = $eventEnd->hour + ($eventEnd->minute / 60);
                                    $topOffset = ($startHour - 8) * 80; // 80px par heure
                                    $height = ($endHour - $startHour) * 80;

                                    // Calculer la largeur et le décalage horizontal
                                    // La grille a 6 colonnes : 1 pour les heures (16.666%) + 5 pour les jours (83.333%)
                                    // Chaque jour occupe donc 83.333% / 5 = 16.666% de la largeur totale
                                    $hoursColumnWidth = 100 / 6; // 16.666%
                                    $daysAreaWidth = 100 - $hoursColumnWidth; // 83.333%
                                    $dayWidth = $daysAreaWidth / 5; // 16.666% par jour

                                    // Si plusieurs événements simultanés, diviser la largeur du jour
                                    $eventWidth = $dayWidth / $totalColumns;

                                    // Position : colonne des heures + décalage du jour + décalage de la colonne de l'événement
                                    $leftOffset = $hoursColumnWidth + ($dayIndex * $dayWidth) + ($column * $eventWidth);
                                @endphp

                                @if($isHomework)
                                    @php
                                        $colorClass = 'bg-zinc-100 border-zinc-400 text-zinc-900 dark:bg-zinc-950 dark:border-zinc-700 dark:text-zinc-200';
                                        $isOverdue = $event->due_date < now() && !$event->completed;
                                    @endphp

                                    <a
                                        href="{{ route('homeworks.edit', $event) }}"
                                        class="absolute rounded border {{ $colorClass }} p-2 text-xs hover:opacity-80 transition-opacity z-10 overflow-hidden"
                                        style="top: {{ $topOffset }}px; left: {{ $leftOffset }}%; width: {{ $eventWidth }}%; height: {{ $height }}px; min-height: 40px;"
                                        wire:navigate
                                        wire:click.stop
                                    >
                                        <div class="flex flex-col h-full">
                                            <div class="flex items-center justify-between gap-1">
                                                <flux:text class="font-semibold text-xs truncate">
                                                    📝 {{ $event->title }}</flux:text>
                                                @if($event->completed)
                                                    <span class="text-green-600 dark:text-green-400 text-sm">✓</span>
                                                @endif
                                            </div>
                                            <flux:text class="mt-1 text-xs">
                                                {{ $event->due_date->format('H:i') }}
                                            </flux:text>
                                            @if($event->subject && $height > 60)
                                                <flux:text class="mt-0.5 text-xs opacity-75 truncate">
                                                    📚 {{ $event->subject }}
                                                </flux:text>
                                            @endif
                                            @if($isOverdue && $height > 80)
                                                <flux:badge size="sm" color="red" class="mt-1">Retard</flux:badge>
                                            @endif
                                        </div>
                                    </a>
                                @elseif($event->type === 'exam')
                                    <div
                                        class="absolute rounded border bg-orange-100 border-orange-400 text-orange-900 dark:bg-orange-950 dark:border-orange-700 dark:text-orange-200 p-2 text-xs z-10 overflow-hidden"
                                        style="top: {{ $topOffset }}px; left: {{ $leftOffset }}%; width: {{ $eventWidth }}%; height: {{ $height }}px; min-height: 40px;"
                                        wire:click.stop
                                    >
                                        <div class="flex flex-col h-full">
                                            <div class="flex items-center justify-between gap-1">
                                                <flux:text class="font-semibold text-xs truncate">
                                                    📋 {{ $event->title }}</flux:text>
                                                <flux:badge size="sm" color="orange" class="shrink-0">EXAMEN</flux:badge>
                                            </div>
                                            <flux:text class="mt-1 text-xs">
                                                {{ $event->start_time->format('H:i') }}
                                                - {{ $event->end_time->format('H:i') }}
                                            </flux:text>
                                            @if($event->room && $height > 60)
                                                <flux:text class="mt-0.5 text-xs opacity-75 truncate">
                                                    📍 {{ $event->room }}
                                                </flux:text>
                                            @endif
                                            @if($event->subject && $height > 80)
                                                <flux:text class="mt-0.5 text-xs opacity-75 truncate">
                                                    📚 {{ $event->subject }}
                                                </flux:text>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    @php
                                        $courseTypeColors = [
                                            'CM' => 'bg-blue-100 border-blue-300 text-blue-900 dark:bg-blue-950 dark:border-blue-800 dark:text-blue-200',
                                            'TD' => 'bg-green-100 border-green-300 text-green-900 dark:bg-green-950 dark:border-green-800 dark:text-green-200',
                                            'TP' => 'bg-purple-100 border-purple-300 text-purple-900 dark:bg-purple-950 dark:border-purple-800 dark:text-purple-200',
                                        ];
                                        $colorClass = ($event->course_type && isset($courseTypeColors[$event->course_type]))
                                            ? $courseTypeColors[$event->course_type]
                                            : 'bg-zinc-100 border-zinc-300 text-zinc-900 dark:bg-zinc-950 dark:border-zinc-800 dark:text-zinc-200';
                                    @endphp

                                    <div
                                        class="absolute rounded border {{ $colorClass }} p-2 text-xs z-10 overflow-hidden"
                                        style="top: {{ $topOffset }}px; left: {{ $leftOffset }}%; width: {{ $eventWidth }}%; height: {{ $height }}px; min-height: 40px;"
                                        wire:click.stop
                                    >
                                        <div class="flex flex-col h-full">
                                            <div class="flex items-center justify-between gap-1">
                                                <flux:text
                                                    class="font-semibold text-xs truncate">{{ $event->title }}</flux:text>
                                                <flux:badge size="sm" color="zinc"
                                                            class="shrink-0">{{ $event->course_type }}</flux:badge>
                                            </div>
                                            <flux:text class="mt-1 text-xs">
                                                {{ $event->start_time->format('H:i') }}
                                                - {{ $event->end_time->format('H:i') }}
                                            </flux:text>
                                            @if($event->room && $height > 60)
                                                <flux:text class="mt-0.5 text-xs opacity-75 truncate">
                                                    📍 {{ $event->room }}
                                                </flux:text>
                                            @endif
                                            @if($event->teacher && $height > 80)
                                                <flux:text class="mt-0.5 text-xs opacity-75 truncate">
                                                    👤 {{ $event->teacher }}
                                                </flux:text>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @elseif($viewMode === 'day')
    {{-- Vue journalière --}}
    <div class="mx-auto max-w-4xl">
        <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            {{-- En-tête du jour --}}
            <div class="grid grid-cols-[140px_1fr] border-b border-zinc-200 dark:border-zinc-700">
                {{-- Colonne des heures --}}
                <div class="border-r border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                        Heures
                    </flux:text>
                </div>

                {{-- Colonne du jour --}}
                <div class="p-4 text-center {{ $selectedDay->isToday() ? 'bg-blue-50 dark:bg-blue-950' : '' }}">
                    <flux:text class="block text-sm font-medium">
                        {{ $selectedDay->isoFormat('dddd') }}
                    </flux:text>
                    <flux:text
                        class="mt-1 block text-lg font-semibold {{ $selectedDay->isToday() ? 'text-blue-600 dark:text-blue-400' : '' }}">
                        {{ $selectedDay->format('d/m/Y') }}
                    </flux:text>
                </div>
            </div>

            {{-- Grille horaire --}}
            <div class="overflow-x-auto">
                <div class="min-w-full">
                    {{-- Conteneur avec position relative pour les événements absolus --}}
                    <div class="relative">
                        {{-- Grille de fond avec les heures --}}
                        <div class="grid grid-cols-[140px_1fr]">
                            @for($hour = 8; $hour <= 18; $hour++)
                                {{-- Colonne des heures --}}
                                <div
                                    class="border-r border-t border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900 h-20">
                                    <flux:text class="text-xs font-medium text-zinc-600 dark:text-zinc-400">
                                        {{ sprintf('%02d:00', $hour) }}
                                    </flux:text>
                                </div>

                                {{-- Colonne du jour (cellule vide cliquable) --}}
                                <div
                                    class="relative border-t border-zinc-200 p-2 dark:border-zinc-700 {{ $selectedDay->isToday() ? 'bg-zinc-50 dark:bg-zinc-900/50' : '' }} cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-950/30 transition-colors group h-20"
                                    wire:click="createHomeworkAt('{{ $selectedDay->format('Y-m-d') }}', {{ $hour }})"
                                    title="Cliquer pour ajouter un devoir à {{ $hour }}h"
                            >
                                {{-- Indicateur visuel au survol --}}
                                <div
                                    class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-0">
                                    <svg class="size-8 text-blue-400 dark:text-blue-500" fill="none"
                                         stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 4v16m8-8H4"/>
                                    </svg>
                                </div>
                            </div>
                        @endfor
                    </div>

                    {{-- Overlay des événements avec position absolue --}}
                    @php
                        $dayStart = $selectedDay->copy()->setTime(8, 0, 0);
                        $dayEnd = $selectedDay->copy()->setTime(18, 59, 59);

                        // Récupérer tous les événements de ce jour
                        $eventsForDay = $this->dayCourses;
                        $homeworksForDay = $this->dayHomeworks;
                        $examsForDay = $this->dayExams;

                        // Combiner et trier par heure de début
                        $allEvents = $eventsForDay->merge($homeworksForDay)->merge($examsForDay)->sortBy(function($event) {
                            return $event->type === 'homework' ? $event->due_date : $event->start_time;
                        })->values();

                        // Calculer les chevauchements pour positionner les événements côte à côte
                        $eventColumns = [];

                        foreach($allEvents as $index => $event) {
                            $eventStart = $event->type === 'homework' ? $event->due_date : $event->start_time;
                            $eventEnd = $event->type === 'homework' ? $event->due_date->copy()->addMinutes(30) : $event->end_time;

                            // Pour les devoirs après 18h, les afficher en bas du créneau 18h
                            if ($event->type === 'homework' && $eventStart->hour >= 18) {
                                $eventStart = $eventStart->copy()->setTime(18, 30, 0);
                                $eventEnd = $eventStart->copy()->addMinutes(30);
                            }

                            // Trouver la première colonne disponible
                            $column = 0;
                            foreach($eventColumns as $col => $colEvents) {
                                $hasOverlap = false;
                                foreach($colEvents as $existingEvent) {
                                    $existingStart = $existingEvent['start'];
                                    $existingEnd = $existingEvent['end'];

                                    if ($eventStart < $existingEnd && $eventEnd > $existingStart) {
                                        $hasOverlap = true;
                                        break;
                                    }
                                }

                                if (!$hasOverlap) {
                                    break;
                                }
                                $column++;
                            }

                            if (!isset($eventColumns[$column])) {
                                $eventColumns[$column] = [];
                            }

                            $eventColumns[$column][] = [
                                'event' => $event,
                                'start' => $eventStart,
                                'end' => $eventEnd,
                                'column' => $column,
                            ];
                        }

                        // Calculer le nombre total de colonnes
                        $totalColumns = count($eventColumns);
                    @endphp

                    {{-- Afficher les événements --}}
                    @foreach($eventColumns as $column => $columnEvents)
                        @foreach($columnEvents as $eventData)
                            @php
                                $event = $eventData['event'];
                                $column = $eventData['column'];

                                $isHomework = $event->type === 'homework';
                                $eventStart = $isHomework ? $event->due_date : $event->start_time;
                                $eventEnd = $isHomework ? $event->due_date->copy()->addMinutes(30) : $event->end_time;

                                // Pour les devoirs après 18h, les afficher en bas du créneau 18h
                                if ($isHomework && $eventStart->hour >= 18) {
                                    $eventStart = $eventStart->copy()->setTime(18, 30, 0);
                                    $eventEnd = $eventStart->copy()->addMinutes(30);
                                }

                                // Calculer la position et la hauteur
                                $startHour = $eventStart->hour + ($eventStart->minute / 60);
                                $endHour = $eventEnd->hour + ($eventEnd->minute / 60);
                                $topOffset = ($startHour - 8) * 80; // 80px par heure
                                $height = ($endHour - $startHour) * 80;

                                // Calculer la largeur et le décalage horizontal
                                // La grille a 2 colonnes : 1 pour les heures (140px fixe) + 1 pour le jour (reste)
                                // Si plusieurs événements simultanés, diviser la largeur du jour
                                $eventWidthPercent = 100 / $totalColumns;

                                // Position : on utilise calc() pour positionner après la colonne des heures (140px)
                                // puis on ajoute le décalage de la colonne de l'événement
                                $leftCalc = "calc(140px + " . ($column * $eventWidthPercent) . "%)";
                                $widthCalc = "calc(" . $eventWidthPercent . "% - 4px)"; // -4px pour un peu d'espace
                            @endphp

                            @if($isHomework)
                                @php
                                    $colorClass = 'bg-zinc-100 border-zinc-400 text-zinc-900 dark:bg-zinc-950 dark:border-zinc-700 dark:text-zinc-200';
                                    $isOverdue = $event->due_date < now() && !$event->completed;
                                @endphp

                                <a
                                    href="{{ route('homeworks.edit', $event) }}"
                                    class="absolute rounded border {{ $colorClass }} p-3 text-sm hover:opacity-80 transition-opacity z-10 overflow-hidden"
                                    style="top: {{ $topOffset }}px; left: {{ $leftCalc }}; width: {{ $widthCalc }}; height: {{ $height }}px; min-height: 60px;"
                                    wire:navigate
                                    wire:click.stop
                                >
                                    <div class="flex flex-col h-full">
                                        <div class="flex items-center justify-between gap-2">
                                            <flux:text class="font-semibold text-sm truncate">
                                                📝 {{ $event->title }}</flux:text>
                                            @if($event->completed)
                                                <span class="text-green-600 dark:text-green-400 text-lg">✓</span>
                                            @endif
                                        </div>
                                        <flux:text class="mt-1 text-sm">
                                            {{ $event->due_date->format('H:i') }}
                                        </flux:text>
                                        @if($event->subject && $height > 80)
                                            <flux:text class="mt-1 text-sm opacity-75 truncate">
                                                📚 {{ $event->subject }}
                                            </flux:text>
                                        @endif
                                        @if($event->description && $height > 120)
                                            <flux:text class="mt-1 text-xs opacity-75 line-clamp-2">
                                                {{ $event->description }}
                                            </flux:text>
                                        @endif
                                        @if($isOverdue && $height > 100)
                                            <flux:badge size="sm" color="red" class="mt-2">En retard</flux:badge>
                                        @endif
                                    </div>
                                </a>
                            @elseif($event->type === 'exam')
                                <div
                                    class="absolute rounded border bg-orange-100 border-orange-400 text-orange-900 dark:bg-orange-950 dark:border-orange-700 dark:text-orange-200 p-3 text-sm z-10 overflow-hidden"
                                    style="top: {{ $topOffset }}px; left: {{ $leftCalc }}; width: {{ $widthCalc }}; height: {{ $height }}px; min-height: 60px;"
                                    wire:click.stop
                                >
                                    <div class="flex flex-col h-full">
                                        <div class="flex items-center justify-between gap-2">
                                            <flux:text class="font-semibold text-sm truncate">
                                                📋 {{ $event->title }}</flux:text>
                                            <flux:badge size="sm" color="orange" class="shrink-0">EXAMEN</flux:badge>
                                        </div>
                                        <flux:text class="mt-1 text-sm">
                                            {{ $event->start_time->format('H:i') }}
                                            - {{ $event->end_time->format('H:i') }}
                                        </flux:text>
                                        @if($event->room && $height > 80)
                                            <flux:text class="mt-1 text-sm opacity-75 truncate">
                                                📍 {{ $event->room }}
                                            </flux:text>
                                        @endif
                                        @if($event->subject && $height > 100)
                                            <flux:text class="mt-1 text-sm opacity-75 truncate">
                                                📚 {{ $event->subject }}
                                            </flux:text>
                                        @endif
                                        @if($event->description && $height > 140)
                                            <flux:text class="mt-1 text-xs opacity-75 line-clamp-2">
                                                {{ $event->description }}
                                            </flux:text>
                                        @endif
                                    </div>
                                </div>
                            @else
                                @php
                                    $courseTypeColors = [
                                        'CM' => 'bg-blue-100 border-blue-300 text-blue-900 dark:bg-blue-950 dark:border-blue-800 dark:text-blue-200',
                                        'TD' => 'bg-green-100 border-green-300 text-green-900 dark:bg-green-950 dark:border-green-800 dark:text-green-200',
                                        'TP' => 'bg-purple-100 border-purple-300 text-purple-900 dark:bg-purple-950 dark:border-purple-800 dark:text-purple-200',
                                    ];
                                    $colorClass = ($event->course_type && isset($courseTypeColors[$event->course_type]))
                                        ? $courseTypeColors[$event->course_type]
                                        : 'bg-zinc-100 border-zinc-300 text-zinc-900 dark:bg-zinc-950 dark:border-zinc-800 dark:text-zinc-200';
                                @endphp

                                <div
                                    class="absolute rounded border {{ $colorClass }} p-3 text-sm z-10 overflow-hidden"
                                    style="top: {{ $topOffset }}px; left: {{ $leftCalc }}; width: {{ $widthCalc }}; height: {{ $height }}px; min-height: 60px;"
                                    wire:click.stop
                                >
                                    <div class="flex flex-col h-full">
                                        <div class="flex items-center justify-between gap-2">
                                            <flux:text
                                                class="font-semibold text-sm truncate">{{ $event->title }}</flux:text>
                                            <flux:badge size="sm" color="zinc"
                                                        class="shrink-0">{{ $event->course_type }}</flux:badge>
                                        </div>
                                        <flux:text class="mt-1 text-sm">
                                            {{ $event->start_time->format('H:i') }}
                                            - {{ $event->end_time->format('H:i') }}
                                        </flux:text>
                                        @if($event->room && $height > 80)
                                            <flux:text class="mt-1 text-sm opacity-75 truncate">
                                                📍 {{ $event->room }}
                                            </flux:text>
                                        @endif
                                        @if($event->teacher && $height > 100)
                                            <flux:text class="mt-1 text-sm opacity-75 truncate">
                                                👤 {{ $event->teacher }}
                                            </flux:text>
                                        @endif
                                        @if($event->description && $height > 140)
                                            <flux:text class="mt-1 text-xs opacity-75 line-clamp-2">
                                                {{ $event->description }}
                                            </flux:text>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    </div>
    @elseif($viewMode === 'month')
    {{-- Vue mensuelle --}}
    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        {{-- En-têtes des jours de la semaine --}}
        <div class="grid grid-cols-7 border-b border-zinc-200 dark:border-zinc-700">
            @foreach(['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $dayName)
                <div class="p-3 text-center bg-zinc-50 dark:bg-zinc-900">
                    <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                        {{ $dayName }}
                    </flux:text>
                </div>
            @endforeach
        </div>

        {{-- Grille du mois --}}
        <div class="grid grid-cols-7">
            @foreach($this->monthDays as $day)
                @php
                    $isCurrentMonth = $day->month === $this->selectedMonth->month;
                    $isToday = $day->isToday();

                    // Récupérer tous les événements de ce jour
                    $dayCoursesCount = $this->monthCourses->filter(function($course) use ($day) {
                        return $course->start_time->isSameDay($day);
                    })->count();

                    $dayHomeworksCount = $this->monthHomeworks->filter(function($homework) use ($day) {
                        return $homework->due_date->isSameDay($day);
                    })->count();

                    $dayExamsCount = $this->monthExams->filter(function($exam) use ($day) {
                        return $exam->start_time->isSameDay($day);
                    })->count();

                    $totalEvents = $dayCoursesCount + $dayHomeworksCount + $dayExamsCount;
                @endphp

                <div
                    class="min-h-24 border-t border-r border-zinc-200 dark:border-zinc-700 p-2 {{ !$isCurrentMonth ? 'bg-zinc-50/50 dark:bg-zinc-900/50' : '' }} {{ $isToday ? 'bg-blue-50 dark:bg-blue-950/30' : '' }} hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors"
                >
                    <div class="flex items-center justify-between mb-1">
                        <flux:text class="text-sm font-medium {{ !$isCurrentMonth ? 'text-zinc-400 dark:text-zinc-600' : '' }} {{ $isToday ? 'text-blue-600 dark:text-blue-400 font-bold' : '' }}">
                            {{ $day->format('j') }}
                        </flux:text>
                        @if($totalEvents > 0)
                            <flux:badge size="sm" color="zinc" class="text-xs">
                                {{ $totalEvents }}
                            </flux:badge>
                        @endif
                    </div>

                    {{-- Afficher les événements du jour (max 3 puis "..." si plus) --}}
                    <div class="space-y-1">
                        @php
                            $maxDisplayedEvents = 3;
                            $dayDisplayedEvents = 0;

                            // Récupérer les événements de ce jour spécifique
                            $dayCourses = $this->monthCourses->filter(function($course) use ($day) {
                                return $course->start_time->isSameDay($day);
                            });
                            $dayExamsFiltered = $this->monthExams->filter(function($exam) use ($day) {
                                return $exam->start_time->isSameDay($day);
                            });
                            $dayHomeworksFiltered = $this->monthHomeworks->filter(function($homework) use ($day) {
                                return $homework->due_date->isSameDay($day);
                            });
                        @endphp

                        {{-- Cours --}}
                        @foreach($dayCourses->take($maxDisplayedEvents - $dayDisplayedEvents) as $course)
                            @php
                                $courseTypeColors = [
                                    'CM' => 'bg-blue-500',
                                    'TD' => 'bg-green-500',
                                    'TP' => 'bg-purple-500',
                                ];
                                $colorClass = ($course->course_type && isset($courseTypeColors[$course->course_type]))
                                    ? $courseTypeColors[$course->course_type]
                                    : 'bg-zinc-500';
                                $dayDisplayedEvents++;
                            @endphp

                            <div class="flex items-center gap-1 text-xs truncate">
                                <div class="w-2 h-2 rounded-full {{ $colorClass }} shrink-0"></div>
                                <span class="truncate">{{ $course->start_time->format('H:i') }} {{ $course->title }}</span>
                            </div>
                        @endforeach

                        {{-- Examens --}}
                        @if($dayDisplayedEvents < $maxDisplayedEvents)
                            @foreach($dayExamsFiltered->take($maxDisplayedEvents - $dayDisplayedEvents) as $exam)
                                @php $dayDisplayedEvents++; @endphp
                                <div class="flex items-center gap-1 text-xs truncate">
                                    <div class="w-2 h-2 rounded-full bg-orange-500 shrink-0"></div>
                                    <span class="truncate">📋 {{ $exam->title }}</span>
                                </div>
                            @endforeach
                        @endif

                        {{-- Devoirs --}}
                        @if($dayDisplayedEvents < $maxDisplayedEvents)
                            @foreach($dayHomeworksFiltered->take($maxDisplayedEvents - $dayDisplayedEvents) as $homework)
                                @php
                                    $colorClass = 'bg-zinc-400';
                                    $dayDisplayedEvents++;
                                @endphp

                                <div class="flex items-center gap-1 text-xs truncate">
                                    <div class="w-2 h-2 rounded-full {{ $colorClass }} shrink-0"></div>
                                    <span class="truncate">📝 {{ $homework->title }}</span>
                                </div>
                            @endforeach
                        @endif

                        {{-- Afficher "..." si plus d'événements --}}
                        @if($totalEvents > $maxDisplayedEvents)
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                +{{ $totalEvents - $maxDisplayedEvents }} autre(s)
                            </flux:text>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Section des devoirs --}}
    @php
        $displayHomeworks = $viewMode === 'month' ? $this->monthHomeworks : ($viewMode === 'day' ? $this->dayHomeworks : $this->homeworks);
        $periodLabel = $viewMode === 'month' ? 'du mois' : ($viewMode === 'day' ? 'du jour' : 'de la semaine');
    @endphp
    @if($displayHomeworks->count() > 0)
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="size-6 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <flux:heading size="sm" class="text-lg font-medium">
                        Devoirs {{ $periodLabel }}
                    </flux:heading>
                </div>
                <flux:button href="{{ route('homeworks.index') }}" variant="ghost" size="sm" wire:navigate>
                    Voir tous les devoirs
                </flux:button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($displayHomeworks as $homework)
                    @php
                        $homeworkBorderClass = 'border-zinc-300 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/30';
                        $isOverdue = $homework->due_date < now() && !$homework->completed;
                    @endphp

                    <a href="{{ route('homeworks.edit', $homework) }}"
                       class="block rounded-lg border p-4 transition-colors hover:border-zinc-400 dark:hover:border-zinc-600 {{ $homeworkBorderClass }}"
                       wire:navigate>
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <flux:text
                                class="font-semibold text-sm {{ $homework->completed ? 'line-through text-zinc-500' : '' }}">
                                {{ $homework->title }}
                            </flux:text>
                            @if($homework->completed)
                                <span class="text-green-600 dark:text-green-400 text-lg">✓</span>
                            @endif
                        </div>

                        <div class="space-y-1 text-xs text-zinc-600 dark:text-zinc-400">
                            <div class="flex items-center gap-1">
                                <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>{{ $homework->due_date->format('D d/m à H:i') }}</span>
                                @if($isOverdue)
                                    <flux:badge size="sm" color="red" class="ml-1">Retard</flux:badge>
                                @endif
                            </div>

                            @if($homework->subject)
                                <div class="flex items-center gap-1">
                                    <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    <span>{{ $homework->subject }}</span>
                                </div>
                            @endif

                            @if($homework->delivery_method)
                                <div class="flex items-center gap-1">
                                    <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span>{{ $homework->delivery_method }}</span>
                                </div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Section des examens --}}
    @php
        $displayExams = $viewMode === 'month' ? $this->monthExams : $this->exams;
    @endphp
    @if($displayExams->count() > 0)
        <div class="rounded-lg border border-orange-200 bg-white p-6 dark:border-orange-700 dark:bg-zinc-800">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="size-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <flux:heading size="sm" class="text-lg font-medium">
                        Examens {{ $periodLabel }}
                    </flux:heading>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($displayExams as $exam)
                    <div
                        class="block rounded-lg border border-orange-300 bg-orange-50 dark:border-orange-700 dark:bg-orange-950/30 p-4 transition-colors hover:border-orange-400 dark:hover:border-orange-600">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <flux:text class="font-semibold text-sm text-orange-900 dark:text-orange-100">
                                📋 {{ $exam->title }}
                            </flux:text>
                            <flux:badge size="sm" color="orange" class="shrink-0">EXAMEN</flux:badge>
                        </div>

                        <div class="space-y-1 text-xs text-orange-700 dark:text-orange-300">
                            <div class="flex items-center gap-1">
                                <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>{{ $exam->start_time->format('D d/m à H:i') }}</span>
                            </div>

                            @if($exam->end_time)
                                <div class="flex items-center gap-1">
                                    <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Durée: {{ $exam->start_time->diffInMinutes($exam->end_time) }} min</span>
                                </div>
                            @endif

                            @if($exam->room)
                                <div class="flex items-center gap-1">
                                    <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span>{{ $exam->room }}</span>
                                </div>
                            @endif

                            @if($exam->subject)
                                <div class="flex items-center gap-1">
                                    <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                    <span>{{ $exam->subject }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    {{-- Section des changements de dernière minute --}}
    <div class="rounded-lg border border-orange-200 bg-orange-50 p-6 dark:border-orange-800 dark:bg-orange-950/30">
        <div class="mb-4 flex items-start gap-3">
            <flux:icon.exclamation-triangle class="size-6 text-orange-600 dark:text-orange-400"/>
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
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full bg-orange-500"></div>
                        <flux:text class="text-sm">Examen</flux:text>
                    </div>
                </div>
            </div>

            {{-- Devoirs --}}
            <div>
                <flux:text class="mb-2 text-sm font-medium text-zinc-600 dark:text-zinc-400">
                    Devoirs
                </flux:text>
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full bg-red-500"></div>
                        <flux:text class="text-sm">Priorité élevée</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full bg-yellow-500"></div>
                        <flux:text class="text-sm">Priorité moyenne</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="h-3 w-3 rounded-full bg-zinc-400"></div>
                        <flux:text class="text-sm">Priorité faible</flux:text>
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
                        <flux:icon.check-circle class="size-4 text-green-600"/>
                        <flux:text class="text-sm">Cours confirmé</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon.exclamation-triangle class="size-4 text-orange-600"/>
                        <flux:text class="text-sm">Changement récent</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon.x-circle class="size-4 text-red-600"/>
                        <flux:text class="text-sm">Cours annulé</flux:text>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions disponibles (pour plus tard) --}}
        <div class="mt-4">
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

