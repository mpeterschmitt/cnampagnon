<?php

use App\Services\IcsImportService;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

use function Livewire\Volt\layout;
use function Livewire\Volt\state;
use function Livewire\Volt\uses;

/**
 * Composant d'importation ICS vers l'emploi du temps (Admin)
 *
 * Permet aux administrateurs d'importer un fichier ICS (iCalendar)
 * pour peupler automatiquement l'emploi du temps.
 */

// Utiliser le trait pour gérer les uploads de fichiers
uses([WithFileUploads::class]);

layout('components.layouts.app');

// État du composant
state([
    'file' => null,                    // Fichier ICS uploadé
    'importing' => false,              // État d'importation en cours
    'previewData' => null,             // Aperçu des données à importer
    'summary' => null,                 // Résumé des événements
    'importResult' => null,            // Résultat de l'importation
    'replaceExisting' => false,        // Option: remplacer l'existant
    'createMissingSubjects' => true,   // Option: créer matières manquantes
    'createMissingTeachers' => true,   // Option: créer enseignants manquants
    'ignorePastEvents' => true,        // Option: ignorer événements passés
]);

/**
 * Action pour traiter le fichier uploadé
 */
$handleFileUpload = function () {
    if (! $this->file) {
        return;
    }

    $this->validate([
        'file' => 'required|file|mimes:ics|max:5120', // 5MB max
    ]);

    $this->importing = true;

    try {
        $service = new IcsImportService;

        // Sauvegarder temporairement le fichier
        $path = $this->file->store('temp-ics');
        $fullPath = Storage::path($path);

        // Valider le fichier
        $validation = $service->validateIcsFile($fullPath);

        if (! $validation['valid']) {
            $this->importing = false;
            \Log::error('Validation failed: '.$validation['error']);
            session()->flash('error', $validation['error']);

            return;
        }

        // Parser le fichier
        $result = $service->parseIcsFile($fullPath);

        $this->previewData = $result['events'];
        $this->summary = $result['summary'];

        // Nettoyer le fichier temporaire
        Storage::delete($path);

        session()->flash('success', 'Fichier analysé avec succès! Vérifiez l\'aperçu ci-dessous.');
    } catch (\Exception $e) {
        \Log::error('Upload exception: '.$e->getMessage());
        \Log::error('Stack trace: '.$e->getTraceAsString());
        session()->flash('error', 'Erreur lors de l\'analyse: '.$e->getMessage());
    } finally {
        $this->importing = false;
    }
};

/**
 * Hook Livewire: appelé automatiquement quand le fichier change
 * Cette méthode est un lifecycle hook de Livewire qui s'exécute automatiquement
 * quand la propriété $file est mise à jour via wire:model
 */
$updatedFile = function () {
    $this->handleFileUpload();
};

/**
 * Action pour confirmer l'importation
 */
$confirmImport = function () {
    if (! $this->previewData) {
        session()->flash('error', 'Aucun événement à importer');

        return;
    }

    $this->importing = true;

    try {
        $service = new IcsImportService;

        $result = $service->importEvents(
            $this->previewData,
            auth()->id(),
            [
                'replace_existing' => $this->replaceExisting,
                'ignore_past_events' => $this->ignorePastEvents,
            ]
        );

        $this->importResult = $result;

        session()->flash('success', "{$result['imported']} événement(s) importé(s) avec succès!");

        // Réinitialiser après succès
        $this->resetForm();
    } catch (\Exception $e) {
        session()->flash('error', 'Erreur lors de l\'importation: '.$e->getMessage());
    } finally {
        $this->importing = false;
    }
};

/**
 * Action pour annuler et réinitialiser
 */
$resetForm = function () {
    $this->file = null;
    $this->previewData = null;
    $this->summary = null;
    $this->importResult = null;
};

?>

<div class="space-y-6">
    {{-- Messages flash --}}
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950/30">
            <div class="flex items-center gap-3">
                <flux:icon.check-circle class="size-5 text-green-600 dark:text-green-400" />
                <flux:text class="text-sm text-green-700 dark:text-green-300">
                    {{ session('success') }}
                </flux:text>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/30">
            <div class="flex items-center gap-3">
                <flux:icon.x-circle class="size-5 text-red-600 dark:text-red-400" />
                <flux:text class="text-sm text-red-700 dark:text-red-300">
                    {{ session('error') }}
                </flux:text>
            </div>
        </div>
    @endif

    {{-- En-tête de la page --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="text-2xl font-semibold">
                Importer depuis ICS
            </flux:heading>
            <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                Importer un fichier iCalendar (.ics) vers l'emploi du temps
            </flux:text>
        </div>

        <flux:button variant="ghost" href="{{ route('admin.users') }}" wire:navigate icon="arrow-left">
            Retour
        </flux:button>
    </div>

    {{-- Guide d'utilisation --}}
    <div class="rounded-lg border border-blue-200 bg-blue-50 p-6 dark:border-blue-800 dark:bg-blue-950/30">
        <div class="flex items-start gap-3">
            <flux:icon.information-circle class="size-6 shrink-0 text-blue-600 dark:text-blue-400" />
            <div>
                <flux:heading size="sm" class="text-lg font-medium text-blue-900 dark:text-blue-200">
                    Comment importer un fichier ICS ?
                </flux:heading>
                <flux:text class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                    Le fichier ICS doit contenir des événements au format iCalendar standard. Chaque événement sera converti en cours dans l'emploi du temps.
                </flux:text>
                <ul class="mt-3 space-y-1 text-sm text-blue-700 dark:text-blue-300">
                    <li>• Format accepté : .ics (iCalendar)</li>
                    <li>• Taille maximale : 5 MB</li>
                    <li>• Les événements existants seront conservés</li>
                    <li>• Vous pourrez prévisualiser avant de confirmer</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Zone d'upload --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-8 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="sm" class="mb-6 text-lg font-medium">
            Sélectionner un fichier ICS
        </flux:heading>

        {{-- Zone de drag & drop --}}
        <div class="rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 p-12 text-center dark:border-zinc-600 dark:bg-zinc-900">
            <flux:icon.document-arrow-up class="mx-auto size-12 text-zinc-400" />

            <flux:heading size="sm" class="mt-4 text-base font-medium">
                Glissez-déposez votre fichier ICS ici
            </flux:heading>

            <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                ou cliquez pour parcourir vos fichiers
            </flux:text>

            <div class="mt-6">
                <input
                    id="file-upload"
                    type="file"
                    wire:model="file"
                    accept=".ics"
                    class="hidden"
                />
                <label for="file-upload" class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-blue-500 dark:hover:bg-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                        <path d="M8.75 2.75a.75.75 0 0 0-1.5 0v5.69L5.03 6.22a.75.75 0 0 0-1.06 1.06l3.5 3.5a.75.75 0 0 0 1.06 0l3.5-3.5a.75.75 0 0 0-1.06-1.06L8.75 8.44V2.75Z" />
                        <path d="M3.5 9.75a.75.75 0 0 0-1.5 0v1.5A2.75 2.75 0 0 0 4.75 14h6.5A2.75 2.75 0 0 0 14 11.25v-1.5a.75.75 0 0 0-1.5 0v1.5c0 .69-.56 1.25-1.25 1.25h-6.5c-.69 0-1.25-.56-1.25-1.25v-1.5Z" />
                    </svg>
                    Choisir un fichier
                </label>
            </div>

            {{-- Show file name after selection --}}
            @if($file)
                <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-950/30">
                    <div class="flex items-center gap-2 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 text-blue-600 dark:text-blue-400">
                            <path fill-rule="evenodd" d="M4 2a1.5 1.5 0 0 0-1.5 1.5v9A1.5 1.5 0 0 0 4 14h8a1.5 1.5 0 0 0 1.5-1.5V6.621a1.5 1.5 0 0 0-.44-1.06L9.94 2.439A1.5 1.5 0 0 0 8.878 2H4Zm4 3.5a.75.75 0 0 1 .75.75v2.69l.72-.72a.75.75 0 1 1 1.06 1.06l-2 2a.75.75 0 0 1-1.06 0l-2-2a.75.75 0 0 1 1.06-1.06l.72.72V6.25A.75.75 0 0 1 8 5.5Z" clip-rule="evenodd" />
                        </svg>
                        <span class="font-medium text-blue-900 dark:text-blue-200">
                            {{ $file->getClientOriginalName() }}
                        </span>
                        <span class="text-blue-700 dark:text-blue-300">
                            ({{ number_format($file->getSize() / 1024, 2) }} KB)
                        </span>
                    </div>
                </div>
            @endif

            {{-- Loading indicator for file upload --}}
            <div wire:loading wire:target="file" class="mt-4 flex items-center justify-center gap-2">
                <svg class="size-5 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <flux:text class="text-sm text-blue-600 dark:text-blue-400">Téléchargement...</flux:text>
            </div>

            <flux:text class="mt-4 text-xs text-zinc-500 dark:text-zinc-500">
                Formats acceptés : .ics • Taille max : 5 MB
            </flux:text>

            {{-- Error display --}}
            @error('file')
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-950/30">
                    <div class="flex items-center gap-2 text-sm text-red-700 dark:text-red-300">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                            <path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 1 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                        </svg>
                        {{ $message }}
                    </div>
                </div>
            @enderror


            @if($importing)
                <div class="mt-4 flex items-center justify-center gap-2">
                    <svg class="size-5 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <flux:text class="text-sm">Analyse en cours...</flux:text>
                </div>
            @endif
        </div>
    </div>

    {{-- Aperçu des données --}}
    @if($previewData && $summary)
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="sm" class="text-lg font-medium">
                    Aperçu des événements
                </flux:heading>
                <div class="flex gap-2">
                    <flux:badge color="blue" size="sm">
                        {{ $summary['total'] }} événement(s)
                    </flux:badge>
                    <flux:badge color="green" size="sm">
                        {{ $summary['courses'] }} cours
                    </flux:badge>
                    @if($summary['exams'] > 0)
                        <flux:badge color="red" size="sm">
                            {{ $summary['exams'] }} examen(s)
                        </flux:badge>
                    @endif
                </div>
            </div>

            <div class="max-h-96 space-y-2 overflow-y-auto">
                @foreach($previewData as $event)
                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex-1">
                            <flux:text class="font-medium">{{ $event['title'] }}</flux:text>
                            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-zinc-600 dark:text-zinc-400">
                                <span>📅 {{ \Carbon\Carbon::parse($event['start_time'])->format('d/m/Y H:i') }}</span>
                                @if($event['room'])
                                    <span>📍 {{ $event['room'] }}</span>
                                @endif
                                @if($event['teacher'])
                                    <span>👤 {{ $event['teacher'] }}</span>
                                @endif
                            </div>
                        </div>
                        @if($event['course_type'])
                            <flux:badge color="blue" size="sm">{{ $event['course_type'] }}</flux:badge>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @elseif(!$previewData)
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="sm" class="text-lg font-medium">
                    Aperçu des événements
                </flux:heading>
                <flux:badge color="zinc" size="sm">
                    0 événement détecté
                </flux:badge>
            </div>

            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:icon.calendar class="mx-auto size-12 text-zinc-400" />
                <flux:text class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                    Aucun fichier uploadé. Les événements apparaîtront ici une fois le fichier chargé.
                </flux:text>
            </div>
        </div>
    @endif

    {{-- Paramètres d'importation --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="sm" class="mb-4 text-lg font-medium">
            Options d'importation
        </flux:heading>

        <div class="space-y-4">
            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <flux:text class="font-medium">Remplacer l'emploi du temps existant</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Supprimer tous les cours importés précédemment avant cette importation
                    </flux:text>
                </div>
                <flux:switch wire:model="replaceExisting" />
            </div>

            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <flux:text class="font-medium">Ignorer les événements passés</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Ne pas importer les cours antérieurs à aujourd'hui
                    </flux:text>
                </div>
                <flux:switch wire:model="ignorePastEvents" />
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex gap-3">
        <flux:button
            variant="primary"
            icon="arrow-down-tray"
            wire:click="confirmImport"
            :disabled="!$previewData || $importing"
        >
            @if($importing)
                Importation...
            @else
                Importer les événements
            @endif
        </flux:button>
        <flux:button variant="ghost" wire:click="resetForm" :disabled="$importing">
            Réinitialiser
        </flux:button>
    </div>
</div>

