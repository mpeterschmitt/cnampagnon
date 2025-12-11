<?php

use App\Models\Event;
use App\Services\PdfImportService;
use Carbon\Carbon;
use Livewire\WithFileUploads;

use function Livewire\Volt\layout;
use function Livewire\Volt\state;
use function Livewire\Volt\uses;

/**
 * Composant d'importation PDF vers l'emploi du temps (Admin)
 *
 * Permet aux administrateurs d'importer un fichier PDF contenant un emploi du temps
 * et d'extraire automatiquement les informations de cours.
 */
layout('components.layouts.app');
uses([WithFileUploads::class]);

// État du composant
state([
    'file' => null,              // Fichier PDF uploadé
    'processing' => false,       // État de traitement en cours
    'extractedData' => null,     // Données extraites du PDF
    'importedCount' => 0,        // Nombre d'événements importés
    'errorMessage' => null,      // Message d'erreur éventuel
    'replaceExisting' => false,  // Remplacer l'emploi du temps existant
    'ignorePassedEvents' => true, // Ignorer les événements passés
]);

/**
 * Traite le fichier PDF uploadé
 * Extraction automatique via Python + OCR
 */
$processPDF = function () {
    $this->validate([
        'file' => 'required|file|mimes:pdf|max:10240', // 10 MB max
    ]);

    $this->processing = true;
    $this->errorMessage = null;

    try {
        // Sauvegarder temporairement le fichier
        $path = $this->file->store('temp', 'local');
        $fullPath = storage_path('app/private/'.$path);

        // Utiliser le service pour extraire les données
        $pdfService = new PdfImportService;
        $result = $pdfService->processFile($fullPath);

        // Stocker les données extraites
        $this->extractedData = $result;

        // Track successful PDF processing with Umami
        umami('pdf_processed', [
            'events_extracted' => count($result['events'] ?? []),
            'processing_method' => $result['method'] ?? 'ocr',
        ]);

        $this->dispatch('processing-complete', [
            'count' => count($result['events'] ?? []),
        ]);
    } catch (\Exception $e) {
        // Track PDF processing error with Umami
        umami('pdf_processing_error', [
            'error_message' => $e->getMessage(),
        ]);

        $this->errorMessage = 'Erreur lors du traitement du PDF : '.$e->getMessage();
        \Log::error('PDF processing error', [
            'error' => $e->getMessage(),
            'file' => $this->file?->getClientOriginalName(),
        ]);
    } finally {
        $this->processing = false;
    }
};

/**
 * Confirme l'importation et sauvegarde les événements en base de données
 */
$confirmImport = function () {
    if (! $this->extractedData || empty($this->extractedData['events'])) {
        $this->errorMessage = 'Aucune donnée à importer.';

        return;
    }

    $this->processing = true;
    $count = 0;

    try {
        // Si on doit remplacer l'emploi du temps existant, supprimer tous les cours
        if ($this->replaceExisting) {
            Event::where('type', 'course')->orWhere('type', 'exam')->delete();
        }

        foreach ($this->extractedData['events'] as $eventData) {
            // Ignorer les événements passés si l'option est activée
            if ($this->ignorePassedEvents) {
                $eventStart = Carbon::parse($eventData['start_time']);
                if ($eventStart->isPast()) {
                    continue;
                }
            }

            Event::create([
                'title' => $eventData['title'] ?? 'Cours sans titre',
                'description' => $eventData['description'] ?? null,
                'teacher' => $eventData['teacher'] ?? null,
                'location' => $eventData['location'] ?? null,
                'start_time' => $eventData['start_time'],
                'end_time' => $eventData['end_time'],
                'type' => $eventData['type'] ?? 'course',
                'color' => $eventData['color'] ?? null,
                'source' => 'pdf_import',
                'created_by' => auth()->id(),
            ]);
            $count++;
        }

        $this->importedCount = $count;

        // Track import event with Umami
        umami('pdf_import', [
            'imported_count' => $count,
            'total_extracted' => count($this->extractedData['events']),
            'replace_existing' => $this->replaceExisting,
            'ignore_passed_events' => $this->ignorePassedEvents,
        ]);

        $this->dispatch('import-confirmed', ['count' => $count]);
        $this->resetForm();
    } catch (\Exception $e) {
        umami('pdf_import_error', [
            'error_message' => $e->getMessage(),
        ]);
        $this->errorMessage = 'Erreur lors de l\'importation : '.$e->getMessage();
        \Log::error('Import error', [
            'error' => $e->getMessage(),
            'data' => $this->extractedData,
        ]);
    } finally {
        $this->processing = false;
    }
};

/**
 * Réinitialise le formulaire
 */
$resetForm = function () {
    $this->file = null;
    $this->extractedData = null;
    $this->errorMessage = null;
};

?>

<div class="space-y-6">
    {{-- En-tête de la page --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="text-2xl font-semibold">
                Importer depuis PDF
            </flux:heading>
            <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                Extraire un emploi du temps depuis un fichier PDF
            </flux:text>
        </div>

        <flux:button variant="ghost" href="{{ route('admin.users') }}" wire:navigate icon="arrow-left">
            Retour
        </flux:button>
    </div>

    {{-- Avertissement --}}
    <div class="rounded-lg border border-orange-200 bg-orange-50 p-6 dark:border-orange-800 dark:bg-orange-950/30">
        <div class="flex items-start gap-3">
            <flux:icon.exclamation-triangle class="size-6 shrink-0 text-orange-600 dark:text-orange-400"/>
            <div>
                <flux:heading size="sm" class="text-lg font-medium text-orange-900 dark:text-orange-200">
                    Extraction automatique avec OCR
                </flux:heading>
                <flux:text class="mt-2 text-sm text-orange-700 dark:text-orange-300">
                    L'extraction depuis PDF utilise la reconnaissance optique de caractères (OCR). La qualité des
                    résultats dépend de la qualité du PDF source. Il est recommandé de vérifier les données extraites
                    avant confirmation.
                </flux:text>
                <ul class="mt-3 space-y-1 text-sm text-orange-700 dark:text-orange-300">
                    <li>• Privilégiez les PDF générés numériquement (non scannés)</li>
                    <li>• Format accepté : .pdf</li>
                    <li>• Taille maximale : 10 MB</li>
                    <li>• Vérification manuelle recommandée</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Messages d'erreur --}}
    @if ($errorMessage)
        <div class="rounded-lg border border-red-200 bg-red-50 p-6 dark:border-red-800 dark:bg-red-950/30">
            <div class="flex items-start gap-3">
                <flux:icon.exclamation-circle class="size-6 shrink-0 text-red-600 dark:text-red-400"/>
                <div>
                    <flux:heading size="sm" class="text-lg font-medium text-red-900 dark:text-red-200">
                        Erreur
                    </flux:heading>
                    <flux:text class="mt-2 text-sm text-red-700 dark:text-red-300">
                        {{ $errorMessage }}
                    </flux:text>
                </div>
            </div>
        </div>
    @endif

    {{-- Message de succès --}}
    @if ($importedCount > 0)
        <div class="rounded-lg border border-green-200 bg-green-50 p-6 dark:border-green-800 dark:bg-green-950/30">
            <div class="flex items-start gap-3">
                <flux:icon.check-circle class="size-6 shrink-0 text-green-600 dark:text-green-400"/>
                <div>
                    <flux:heading size="sm" class="text-lg font-medium text-green-900 dark:text-green-200">
                        Importation réussie
                    </flux:heading>
                    <flux:text class="mt-2 text-sm text-green-700 dark:text-green-300">
                        {{ $importedCount }} événement(s) ont été importés avec succès.
                    </flux:text>
                </div>
            </div>
        </div>
    @endif

    {{-- Zone d'upload --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-8 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="sm" class="mb-6 text-lg font-medium">
            Sélectionner un fichier PDF
        </flux:heading>

        {{-- Zone de drag & drop --}}
        <div
            class="rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 p-12 text-center dark:border-zinc-600 dark:bg-zinc-900">
            <flux:icon.document-text class="mx-auto size-12 text-zinc-400"/>

            <flux:heading size="sm" class="mt-4 text-base font-medium">
                @if ($file && is_object($file))
                    Fichier sélectionné : {{ $file->getClientOriginalName() }}
                @else
                    Glissez-déposez votre fichier PDF ici
                @endif
            </flux:heading>

            <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                ou cliquez pour parcourir vos fichiers
            </flux:text>

            <div class="mt-6">
                <input
                    type="file"
                    id="pdf-upload"
                    wire:model="file"
                    accept=".pdf"
                    class="hidden"
                />
                <label for="pdf-upload" class="inline-block cursor-pointer">
                    <span
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M9.25 13.25a.75.75 0 001.5 0V4.636l2.955 3.129a.75.75 0 001.09-1.03l-4.25-4.5a.75.75 0 00-1.09 0l-4.25 4.5a.75.75 0 101.09 1.03L9.25 4.636v8.614z"/>
                            <path
                                d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z"/>
                        </svg>
                        Choisir un fichier
                    </span>
                </label>
            </div>

            <flux:text class="mt-4 text-xs text-zinc-500 dark:text-zinc-500">
                Formats acceptés : .pdf • Taille max : 10 MB
            </flux:text>

            {{-- Loading indicator pour l'upload --}}
            <div wire:loading wire:target="file" class="mt-4">
                <flux:text class="text-sm text-blue-600 dark:text-blue-400">
                    Chargement du fichier...
                </flux:text>
            </div>

            {{-- Debug info --}}
            @if ($file)
                <div class="mt-4 text-xs text-green-600 dark:text-green-400">
                    ✓ Fichier chargé: {{ $file->getClientOriginalName() }}
                    ({{ number_format($file->getSize() / 1024, 2) }} KB)
                </div>
            @endif
        </div>
    </div>

    {{-- Options d'importation --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="sm" class="mb-4 text-lg font-medium">
            Options d'importation
        </flux:heading>

        <div class="space-y-4">
            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <flux:text class="font-medium">Remplacer l'emploi du temps existant</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Supprimer tous les cours actuels avant l'import
                    </flux:text>
                </div>
                <flux:switch wire:model.live="replaceExisting" />
            </div>

            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <flux:text class="font-medium">Ignorer les événements passés</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Ne pas importer les cours dont la date est déjà passée
                    </flux:text>
                </div>
                <flux:switch wire:model.live="ignorePassedEvents" />
            </div>
        </div>
    </div>

    {{-- Aperçu du fichier (placeholder) --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading size="sm" class="text-lg font-medium">
                Aperçu du document
            </flux:heading>
            <flux:badge color="zinc" size="sm">
                Aucun fichier
            </flux:badge>
        </div>

        {{-- État vide --}}
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.document-magnifying-glass class="mx-auto size-12 text-zinc-400"/>
            <flux:text class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                Aucun document chargé. L'aperçu apparaîtra ici une fois le fichier uploadé.
            </flux:text>
        </div>

        {{-- Placeholder pour l'aperçu PDF --}}
        {{-- Une fois implémenté, afficher une miniature du PDF ici --}}
    </div>

    {{-- Données extraites --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading size="sm" class="text-lg font-medium">
                Données extraites
            </flux:heading>
            <flux:badge color="{{ $extractedData ? 'blue' : 'zinc' }}" size="sm">
                {{ $extractedData ? count($extractedData['events'] ?? []) : 0 }} cours
                détecté{{ $extractedData && count($extractedData['events'] ?? []) > 1 ? 's' : '' }}
            </flux:badge>
        </div>

        @if ($extractedData && !empty($extractedData['events']))
            {{-- Affichage des données extraites --}}
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @foreach ($extractedData['events'] as $index => $event)
                    <div
                        class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div>
                            <flux:text class="font-medium">{{ $event['title'] ?? 'Cours sans titre' }}</flux:text>
                            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ Carbon::parse($event['start_time'])->format('l d/m/Y • H:i') }} -
                                {{ Carbon::parse($event['end_time'])->format('H:i') }}
                                @if (!empty($event['location']))
                                    • {{ $event['location'] }}
                                @endif
                            </flux:text>
                            @if (!empty($event['teacher']))
                                <flux:text class="text-xs text-zinc-500 dark:text-zinc-500">
                                    Prof. {{ $event['teacher'] }} • {{ ucfirst($event['type'] ?? 'course') }}
                                </flux:text>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- État vide --}}
            <div
                class="rounded-lg border border-zinc-200 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:icon.cpu-chip class="mx-auto size-12 text-zinc-400"/>
                <flux:text class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                    Les données extraites apparaîtront ici après le traitement du PDF.
                </flux:text>
                <flux:text class="mt-2 text-xs text-zinc-500 dark:text-zinc-500">
                    Vous pourrez les vérifier et les modifier avant l'importation finale
                </flux:text>
            </div>
        @endif
    </div>

    {{-- Statistiques d'extraction --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                Cours détectés
            </flux:text>
            <flux:heading size="lg" class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">
                {{ $extractedData ? count($extractedData['events'] ?? []) : 0 }}
            </flux:heading>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                Confiance moyenne
            </flux:text>
            <flux:heading size="lg" class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">
                - %
            </flux:heading>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                À vérifier
            </flux:text>
            <flux:heading size="lg" class="mt-2 text-3xl font-bold text-orange-600 dark:text-orange-400">
                0
            </flux:heading>
        </div>
    </div>

    {{-- Guide de vérification --}}
    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="sm" class="mb-3 text-lg font-medium">
            Points à vérifier avant importation
        </flux:heading>
        <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
            <li class="flex items-start gap-2">
                <flux:icon.check-circle class="size-5 shrink-0 text-zinc-400"/>
                <span>Vérifier que toutes les dates sont correctement extraites</span>
            </li>
            <li class="flex items-start gap-2">
                <flux:icon.check-circle class="size-5 shrink-0 text-zinc-400"/>
                <span>Confirmer les heures de début et de fin de chaque cours</span>
            </li>
            <li class="flex items-start gap-2">
                <flux:icon.check-circle class="size-5 shrink-0 text-zinc-400"/>
                <span>Vérifier l'orthographe des noms de matières et enseignants</span>
            </li>
            <li class="flex items-start gap-2">
                <flux:icon.check-circle class="size-5 shrink-0 text-zinc-400"/>
                <span>Confirmer les numéros de salles</span>
            </li>
            <li class="flex items-start gap-2">
                <flux:icon.check-circle class="size-5 shrink-0 text-zinc-400"/>
                <span>S'assurer que les types de cours (CM/TD/TP) sont corrects</span>
            </li>
        </ul>
    </div>

    {{-- Actions --}}
    <div class="flex gap-3">
        @if (!$file || $processing)
            <flux:button
                variant="primary"
                icon="cpu-chip"
                disabled
            >
                <span>Lancer l'extraction</span>
            </flux:button>
        @else
            <flux:button
                variant="primary"
                icon="cpu-chip"
                wire:click="processPDF"
                wire:loading.attr="disabled"
                wire:target="processPDF"
            >
                <span wire:loading.remove wire:target="processPDF">Lancer l'extraction</span>
                <span wire:loading wire:target="processPDF">Traitement en cours...</span>
            </flux:button>
        @endif

        @if (!$extractedData || $processing)
            <flux:button
                variant="outline"
                icon="arrow-down-tray"
                disabled
            >
                <span>Importer les données</span>
            </flux:button>
        @else
            <flux:button
                variant="outline"
                icon="arrow-down-tray"
                wire:click="confirmImport"
                wire:loading.attr="disabled"
                wire:target="confirmImport"
            >
                <span wire:loading.remove wire:target="confirmImport">Importer les données</span>
                <span wire:loading wire:target="confirmImport">Importation...</span>
            </flux:button>
        @endif

        @if ($processing)
            <flux:button
                variant="ghost"
                disabled
            >
                Réinitialiser
            </flux:button>
        @else
            <flux:button
                variant="ghost"
                wire:click="resetForm"
            >
                Réinitialiser
            </flux:button>
        @endif
    </div>
</div>

