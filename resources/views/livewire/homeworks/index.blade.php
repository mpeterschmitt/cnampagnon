<?php

use function Livewire\Volt\{computed, layout, state};
use App\Models\Event;

/**
 * Composant pour la liste des devoirs
 *
 * Permet de visualiser, filtrer et gérer les devoirs
 */
layout('components.layouts.app');

state([
    'filter' => 'all', // all, incomplete, completed, upcoming, overdue
    'selectedSubject' => null,
    'selectedPriority' => null,
    'search' => '',
]);

/**
 * Computed property pour obtenir les devoirs filtrés
 */
$homeworks = computed(function () {
    $query = Event::homework();

    // Filtrer par statut
    match ($this->filter) {
        'incomplete' => $query->incomplete(),
        'completed' => $query->where('completed', true),
        'upcoming' => $query->upcoming()->incomplete(),
        'overdue' => $query->where('due_date', '<', now())->incomplete(),
        default => $query,
    };

    // Filtrer par matière
    if ($this->selectedSubject) {
        $query->forSubject($this->selectedSubject);
    }

    // Filtrer par priorité
    if ($this->selectedPriority) {
        $query->where('priority', $this->selectedPriority);
    }

    // Recherche
    if ($this->search) {
        $query->where(function ($q) {
            $q->where('title', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%");
        });
    }

    return $query->orderBy('due_date')->get();
});

/**
 * Computed property pour obtenir les matières disponibles
 */
$subjects = computed(function () {
    return Event::homework()
        ->whereNotNull('subject')
        ->distinct()
        ->pluck('subject')
        ->sort()
        ->values();
});

/**
 * Marquer un devoir comme complété/non complété
 */
$toggleCompleted = function (int $homeworkId) {
    $homework = Event::findOrFail($homeworkId);
    $homework->completed = ! $homework->completed;
    $homework->updated_by = auth()->id();
    $homework->save();
};

/**
 * Supprimer un devoir
 */
$delete = function (int $homeworkId) {
    $homework = Event::findOrFail($homeworkId);
    $homework->delete();
};

/**
 * Réinitialiser les filtres
 */
$resetFilters = function () {
    $this->filter = 'all';
    $this->selectedSubject = null;
    $this->selectedPriority = null;
    $this->search = '';
};

?>

<div class="space-y-6">
    {{-- En-tête de la page --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="text-2xl font-semibold">
                Devoirs
            </flux:heading>
            <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                Gérez vos devoirs et dates de rendu
            </flux:text>
        </div>

        {{-- Action d'ajout --}}
        <div>
            <flux:button href="{{ route('homeworks.create') }}" icon="plus">
                Ajouter un devoir
            </flux:button>
        </div>
    </div>

    {{-- Filtres et recherche --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading size="sm" class="text-lg font-medium">
                Filtres
            </flux:heading>
            @if ($search || $filter !== 'all' || $selectedSubject || $selectedPriority)
                <flux:button variant="ghost" size="sm" wire:click="resetFilters">
                    Réinitialiser
                </flux:button>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Recherche --}}
            <div>
                <flux:field>
                    <flux:label>Recherche</flux:label>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Titre ou description..."
                    />
                </flux:field>
            </div>

            {{-- Filtre par statut --}}
            <div>
                <flux:field>
                    <flux:label>Statut</flux:label>
                    <flux:select wire:model.live="filter" placeholder="Tous les devoirs">
                        <option value="all">Tous les devoirs</option>
                        <option value="incomplete">Non complétés</option>
                        <option value="completed">Complétés</option>
                        <option value="upcoming">À venir</option>
                        <option value="overdue">En retard</option>
                    </flux:select>
                </flux:field>
            </div>

            {{-- Filtre par matière --}}
            <div>
                <flux:field>
                    <flux:label>Matière</flux:label>
                    <flux:select wire:model.live="selectedSubject" placeholder="Toutes les matières">
                        @foreach ($this->subjects as $subject)
                            <option value="{{ $subject }}">{{ $subject }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>

            {{-- Filtre par priorité --}}
            <div>
                <flux:field>
                    <flux:label>Priorité</flux:label>
                    <flux:select wire:model.live="selectedPriority" placeholder="Toutes les priorités">
                        <option value="low">Faible</option>
                        <option value="medium">Moyenne</option>
                        <option value="high">Élevée</option>
                    </flux:select>
                </flux:field>
            </div>
        </div>
    </div>

    {{-- Liste des devoirs --}}
    <div class="space-y-4">
        @forelse ($this->homeworks as $homework)
            <div
                wire:key="homework-{{ $homework->id }}"
                class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-600 transition-colors"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-4 flex-1">
                        {{-- Checkbox pour marquer comme complété --}}
                        <div class="pt-1">
                            <flux:checkbox
                                wire:click="toggleCompleted({{ $homework->id }})"
                                :checked="$homework->completed"
                            />
                        </div>

                        <div class="flex-1">
                            {{-- Titre et badges --}}
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <flux:heading size="lg" class="{{ $homework->completed ? 'line-through text-zinc-500 dark:text-zinc-600' : '' }}">
                                    {{ $homework->title }}
                                </flux:heading>

                                @if ($homework->priority === 'high')
                                    <flux:badge color="red" size="sm">Priorité élevée</flux:badge>
                                @elseif ($homework->priority === 'medium')
                                    <flux:badge color="yellow" size="sm">Priorité moyenne</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">Priorité faible</flux:badge>
                                @endif

                                @if ($homework->due_date < now() && !$homework->completed)
                                    <flux:badge color="red" size="sm">En retard</flux:badge>
                                @endif
                            </div>

                            {{-- Description --}}
                            @if ($homework->description)
                                <flux:text class="mb-3">
                                    {{ $homework->description }}
                                </flux:text>
                            @endif

                            {{-- Métadonnées --}}
                            <div class="flex flex-wrap gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                                @if ($homework->subject)
                                    <span class="flex items-center gap-1">
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                        {{ $homework->subject }}
                                    </span>
                                @endif

                                @if ($homework->teacher)
                                    <span class="flex items-center gap-1">
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        {{ $homework->teacher }}
                                    </span>
                                @endif

                                <span class="flex items-center gap-1">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    À rendre le {{ $homework->due_date->format('d/m/Y à H:i') }}
                                </span>

                                @if ($homework->location)
                                    <span class="flex items-center gap-1">
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ $homework->location }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-start gap-2">
                        <flux:button
                            href="{{ route('homeworks.edit', $homework) }}"
                            variant="ghost"
                            size="sm"
                        >
                            Modifier
                        </flux:button>

                        <flux:button
                            wire:click="delete({{ $homework->id }})"
                            wire:confirm="Êtes-vous sûr de vouloir supprimer ce devoir ?"
                            variant="ghost"
                            size="sm"
                            color="red"
                        >
                            Supprimer
                        </flux:button>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-800">
                <svg class="mx-auto size-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <flux:heading size="lg" class="mt-4 mb-2">Aucun devoir trouvé</flux:heading>
                <flux:text>
                    @if ($search || $filter !== 'all' || $selectedSubject || $selectedPriority)
                        Aucun devoir ne correspond à vos critères de recherche.
                    @else
                        Commencez par ajouter votre premier devoir.
                    @endif
                </flux:text>
                @if (!$search && $filter === 'all' && !$selectedSubject && !$selectedPriority)
                    <flux:button href="{{ route('homeworks.create') }}" class="mt-4">
                        Ajouter un devoir
                    </flux:button>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Statistiques --}}
    @if ($this->homeworks->count() > 0)
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="sm" class="mb-4 text-lg font-medium">
                Statistiques
            </flux:heading>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <div>
                    <div class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">
                        {{ $this->homeworks->count() }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Total</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-green-600 dark:text-green-500">
                        {{ $this->homeworks->where('completed', true)->count() }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Complétés</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-500">
                        {{ $this->homeworks->where('completed', false)->count() }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">En cours</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-red-600 dark:text-red-500">
                        {{ $this->homeworks->where('completed', false)->where('due_date', '<', now())->count() }}
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">En retard</div>
                </div>
            </div>
        </div>
    @endif
</div>

