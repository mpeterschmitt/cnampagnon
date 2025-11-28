<?php

use Carbon\Carbon;
use function Livewire\Volt\{computed, layout, rules, state, mount};
use App\Http\Requests\StoreHomeworkRequest;
use App\Models\Event;

/**
 * Composant pour créer ou modifier un devoir
 */
layout('components.layouts.app');

state(['homework', 'form']);

/**
 * Mount hook pour initialiser le composant
 */
mount(function ($homework = null) {
    // Résoudre l'ID du devoir si c'est un string
    if (is_string($homework) || is_numeric($homework)) {
        $homework = Event::findOrFail($homework);
    }

    $this->homework = $homework;

    // Récupérer la date de rendu depuis les query params si disponible (venant du calendrier)
    $dueDateFromQuery = request()->query('due_date');
    $defaultDueDate = $dueDateFromQuery
        ? Carbon::parse($dueDateFromQuery)->format('Y-m-d\TH:i')
        : now()->addWeek()->format('Y-m-d\TH:i');

    // Récupérer la matière et l'enseignant depuis les query params (auto-rempli depuis le calendrier)
    $subjectFromQuery = request()->query('subject');
    $teacherFromQuery = request()->query('teacher');

    // Pour l'édition, ne pré-remplir start_time/end_time que s'ils diffèrent des valeurs par défaut
    // Par défaut : start_time = due_date + 1 min, end_time = due_date + 2 min
    $startTime = '';
    $endTime = '';

    if ($homework && $homework->due_date) {
        $expectedStartTime = $homework->due_date->copy()->addMinute();
        $expectedEndTime = $homework->due_date->copy()->addMinutes(2);

        // Afficher start_time seulement s'il diffère de due_date + 1 minute
        if ($homework->start_time &&
            $homework->start_time->format('Y-m-d H:i') !== $expectedStartTime->format('Y-m-d H:i')) {
            $startTime = $homework->start_time->format('Y-m-d\TH:i');
        }

        // Afficher end_time seulement s'il diffère de due_date + 2 minutes
        if ($homework->end_time &&
            $homework->end_time->format('Y-m-d H:i') !== $expectedEndTime->format('Y-m-d H:i')) {
            $endTime = $homework->end_time->format('Y-m-d\TH:i');
        }
    }

    $this->form = [
        'title' => $homework?->title ?? '',
        'description' => $homework?->description ?? '',
        'subject' => $homework?->subject ?? $subjectFromQuery ?? '',
        'teacher' => $homework?->teacher ?? $teacherFromQuery ?? '',
        'due_date' => $homework?->due_date?->format('Y-m-d\TH:i') ?? $defaultDueDate,
        'priority' => $homework?->priority ?? 'medium',
        'start_time' => $startTime,
        'end_time' => $endTime,
        'location' => $homework?->location ?? '',
        'color' => $homework?->color ?? '#3b82f6',
    ];
});

/**
 * Règles de validation
 */
rules([
    'form.title' => ['required', 'string', 'max:255'],
    'form.description' => ['nullable', 'string', 'max:5000'],
    'form.subject' => ['nullable', 'string', 'max:255'],
    'form.teacher' => ['nullable', 'string', 'max:255'],
    'form.due_date' => ['required', 'date'],
    'form.priority' => ['required', 'in:low,medium,high'],
    'form.start_time' => ['nullable', 'date'],
    'form.end_time' => ['nullable', 'date', 'after:form.start_time'],
    'form.location' => ['nullable', 'string', 'max:255'],
    'form.color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
]);

/**
 * Messages de validation personnalisés
 */
$messages = [
    'form.title.required' => 'Le titre du devoir est obligatoire.',
    'form.title.max' => 'Le titre ne peut pas dépasser 255 caractères.',
    'form.due_date.required' => 'La date de rendu est obligatoire.',
    'form.priority.required' => 'La priorité est obligatoire.',
    'form.priority.in' => 'La priorité doit être low, medium ou high.',
    'form.end_time.after' => 'La date de fin doit être après la date de début.',
    'form.color.regex' => 'La couleur doit être au format hexadécimal (#000000).',
];

/**
 * Computed property pour obtenir les matières disponibles
 */
$subjects = computed(function () {
    return Event::whereNotNull('subject')
        ->distinct()
        ->pluck('subject')
        ->sort()
        ->values();
});

/**
 * Sauvegarder le devoir
 */
$save = function () {
    $this->validate();

    $data = [
        'type' => 'homework',
        'title' => $this->form['title'],
        'description' => $this->form['description'],
        'subject' => $this->form['subject'],
        'teacher' => $this->form['teacher'],
        'due_date' => $this->form['due_date'],
        'priority' => $this->form['priority'],
        'location' => $this->form['location'],
        'color' => $this->form['color'],
        'source' => 'manual',
    ];

    // Pour les devoirs, start_time et end_time sont optionnels dans le formulaire
    // Gestion intelligente des dates :
    // - Si start_time est fourni : utiliser start_time, et end_time = start_time + 1 min (si non fourni)
    // - Si start_time est vide : start_time = due_date + 1 min, end_time = due_date + 2 min (si non fourni)
    $dueDate = Carbon::parse($this->form['due_date']);

    if ($this->form['start_time']) {
        $data['start_time'] = $this->form['start_time'];
        if ($this->form['end_time']) {
            $data['end_time'] = $this->form['end_time'];
        } else {
            // end_time = start_time + 1 minute
            $startTime = Carbon::parse($this->form['start_time']);
            $data['end_time'] = $startTime->copy()->addMinute()->format('Y-m-d H:i:s');
        }
    } else {
        // Pas de start_time fourni : start_time = due_date + 1 minute
        $data['start_time'] = $dueDate->copy()->addMinute()->format('Y-m-d H:i:s');
        if ($this->form['end_time']) {
            $data['end_time'] = $this->form['end_time'];
        } else {
            // end_time = due_date + 2 minutes (ou start_time + 1 minute)
            $data['end_time'] = $dueDate->copy()->addMinutes(2)->format('Y-m-d H:i:s');
        }
    }

    if ($this->homework) {
        // Modification
        $data['updated_by'] = auth()->id();
        $this->homework->update($data);
        session()->flash('success', 'Devoir modifié avec succès !');
    } else {
        // Création
        $data['created_by'] = auth()->id();
        $data['completed'] = false;
        Event::create($data);
        session()->flash('success', 'Devoir créé avec succès !');
    }

    $this->redirect(route('homeworks.index'), navigate: true);
};

/**
 * Annuler et retourner à la liste
 */
$cancel = function () {
    $this->redirect(route('homeworks.index'), navigate: true);
};

?>

<div class="space-y-6">
    {{-- En-tête de la page --}}
    <div>
        <flux:heading size="xl" class="text-2xl font-semibold">
            {{ $homework ? 'Modifier le devoir' : 'Ajouter un devoir' }}
        </flux:heading>
        <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
            {{ $homework ? 'Modifiez les informations du devoir' : 'Créez un nouveau devoir' }}
        </flux:text>
    </div>

    {{-- Formulaire --}}
    <form wire:submit="save">
        <div class="space-y-6">
            {{-- Carte principale --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="space-y-6">
                    {{-- Titre --}}
                    <flux:field>
                        <flux:label>Titre *</flux:label>
                        <flux:input
                            wire:model="form.title"
                            placeholder="Ex: DM de Mathématiques"
                            required
                        />
                        <flux:error name="form.title"/>
                    </flux:field>

                    {{-- Description --}}
                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea
                            wire:model="form.description"
                            placeholder="Détails du devoir..."
                            rows="4"
                        />
                        <flux:error name="form.description"/>
                    </flux:field>

                    {{-- Matière et Enseignant --}}
                    <div class="grid gap-6 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Matière</flux:label>
                            <flux:input
                                wire:model="form.subject"
                                placeholder="Ex: Mathématiques"
                                list="subjects-list"
                            />
                            <datalist id="subjects-list">
                                @foreach ($this->subjects as $subject)
                                    <option value="{{ $subject }}">
                                @endforeach
                            </datalist>
                            <flux:error name="form.subject"/>
                        </flux:field>

                        <flux:field>
                            <flux:label>Enseignant</flux:label>
                            <flux:input
                                wire:model="form.teacher"
                                placeholder="Ex: M. Dupont"
                            />
                            <flux:error name="form.teacher"/>
                        </flux:field>
                    </div>

                    {{-- Priorité --}}
                    <flux:field>
                        <flux:label>Priorité *</flux:label>
                        <flux:select wire:model="form.priority" required>
                            <option value="low">Faible</option>
                            <option value="medium">Moyenne</option>
                            <option value="high">Élevée</option>
                        </flux:select>
                        <flux:error name="form.priority"/>
                    </flux:field>

                    {{-- Date de rendu --}}
                    <flux:field>
                        <flux:label>Date de rendu *</flux:label>
                        <flux:input
                            wire:model="form.due_date"
                            type="datetime-local"
                            required
                        />
                        <flux:error name="form.due_date"/>
                    </flux:field>
                </div>
            </div>

            {{-- Options avancées --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="sm" class="mb-4 text-lg font-medium">
                    Options avancées (optionnel)
                </flux:heading>

                <div class="space-y-6">
                    {{-- Dates de début et fin --}}
                    <div class="grid gap-6 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>Date de début</flux:label>
                            <flux:input
                                wire:model="form.start_time"
                                type="datetime-local"
                            />
                            <flux:error name="form.start_time"/>
                            <flux:text class="text-xs text-zinc-500">
                                Optionnel : date de début du travail
                            </flux:text>
                        </flux:field>

                        <flux:field>
                            <flux:label>Date de fin</flux:label>
                            <flux:input
                                wire:model="form.end_time"
                                type="datetime-local"
                            />
                            <flux:error name="form.end_time"/>
                            <flux:text class="text-xs text-zinc-500">
                                Par défaut : 1 minute après la date de début
                            </flux:text>
                        </flux:field>
                    </div>

                    {{-- Lieu --}}
                    <flux:field>
                        <flux:label>Lieu</flux:label>
                        <flux:input
                            wire:model="form.location"
                            placeholder="Ex: Salle B101, À rendre en ligne"
                        />
                        <flux:error name="form.location"/>
                    </flux:field>

                    {{-- Couleur --}}
                    <flux:field>
                        <flux:label>Couleur</flux:label>
                        <div class="flex gap-3 items-center">
                            <flux:input
                                wire:model="form.color"
                                type="color"
                                class="h-10 w-20"
                            />
                            <flux:input
                                wire:model="form.color"
                                placeholder="#3b82f6"
                                class="flex-1"
                            />
                        </div>
                        <flux:error name="form.color"/>
                    </flux:field>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3">
                <flux:button
                    type="button"
                    wire:click="cancel"
                    variant="ghost"
                >
                    Annuler
                </flux:button>
                <flux:button
                    type="submit"
                    variant="primary"
                >
                    {{ $homework ? 'Enregistrer les modifications' : 'Créer le devoir' }}
                </flux:button>
            </div>
        </div>
    </form>
</div>

