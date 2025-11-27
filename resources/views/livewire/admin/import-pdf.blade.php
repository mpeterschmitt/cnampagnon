<?php

use function Livewire\Volt\{layout, state};

/**
 * Composant d'importation PDF vers l'emploi du temps (Admin)
 *
 * Permet aux administrateurs d'importer un fichier PDF contenant un emploi du temps
 * et d'extraire automatiquement les informations de cours.
 */

layout('components.layouts.app');

// État du composant
state([
    'file' => null,              // Fichier PDF uploadé
    'processing' => false,       // État de traitement en cours
    'extractedData' => null,     // Données extraites du PDF
    'ocrQuality' => 'high',      // Qualité de l'OCR (low, medium, high)
]);

/**
 * Action pour traiter le fichier PDF
 * TODO: Implémenter l'extraction OCR du PDF
 */
$processPDF = function () {
    $this->processing = true;
    // Placeholder: Simuler le traitement
    sleep(2);
    $this->processing = false;
    $this->dispatch('processing-complete');
};

/**
 * Action pour confirmer l'importation
 * TODO: Implémenter l'insertion en base de données
 */
$confirmImport = function () {
    // Placeholder
    $this->dispatch('import-confirmed');
};

/**
 * Action pour annuler et réinitialiser
 */
$resetForm = function () {
    $this->file = null;
    $this->extractedData = null;
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
            <flux:icon.exclamation-triangle class="size-6 shrink-0 text-orange-600 dark:text-orange-400" />
            <div>
                <flux:heading size="sm" class="text-lg font-medium text-orange-900 dark:text-orange-200">
                    Extraction automatique avec OCR
                </flux:heading>
                <flux:text class="mt-2 text-sm text-orange-700 dark:text-orange-300">
                    L'extraction depuis PDF utilise la reconnaissance optique de caractères (OCR). La qualité des résultats dépend de la qualité du PDF source. Il est recommandé de vérifier les données extraites avant confirmation.
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

    {{-- Zone d'upload --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-8 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="sm" class="mb-6 text-lg font-medium">
            Sélectionner un fichier PDF
        </flux:heading>

        {{-- Zone de drag & drop --}}
        <div class="rounded-lg border-2 border-dashed border-zinc-300 bg-zinc-50 p-12 text-center dark:border-zinc-600 dark:bg-zinc-900">
            <flux:icon.document-text class="mx-auto size-12 text-zinc-400" />

            <flux:heading size="sm" class="mt-4 text-base font-medium">
                Glissez-déposez votre fichier PDF ici
            </flux:heading>

            <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                ou cliquez pour parcourir vos fichiers
            </flux:text>

            <div class="mt-6">
                <flux:button variant="primary" icon="arrow-up-tray">
                    Choisir un fichier
                </flux:button>
            </div>

            <flux:text class="mt-4 text-xs text-zinc-500 dark:text-zinc-500">
                Formats acceptés : .pdf • Taille max : 10 MB
            </flux:text>
        </div>

        {{-- TODO: Wire up file input --}}
        {{-- <input type="file" wire:model="file" accept=".pdf" class="hidden" /> --}}
    </div>

    {{-- Paramètres d'extraction --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="sm" class="mb-4 text-lg font-medium">
            Paramètres d'extraction OCR
        </flux:heading>

        <div class="space-y-4">
            <div>
                <flux:field>
                    <flux:label>Qualité de l'OCR</flux:label>
                    <flux:select wire:model="ocrQuality">
                        <option value="low">Rapide (basse qualité)</option>
                        <option value="medium">Standard (qualité moyenne)</option>
                        <option value="high">Précis (haute qualité - plus lent)</option>
                    </flux:select>
                    <flux:text class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
                        Une qualité plus élevée améliore la précision mais augmente le temps de traitement
                    </flux:text>
                </flux:field>
            </div>

            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <flux:text class="font-medium">Détection automatique des colonnes</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Identifier automatiquement les jours et heures
                    </flux:text>
                </div>
                <flux:switch disabled />
            </div>

            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <flux:text class="font-medium">Correction orthographique</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Corriger les erreurs courantes d'OCR
                    </flux:text>
                </div>
                <flux:switch disabled />
            </div>

            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <flux:text class="font-medium">Extraction intelligente</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Utiliser l'IA pour améliorer la reconnaissance
                    </flux:text>
                </div>
                <flux:switch disabled />
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
            <flux:icon.document-magnifying-glass class="mx-auto size-12 text-zinc-400" />
            <flux:text class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                Aucun document chargé. L'aperçu apparaîtra ici une fois le fichier uploadé.
            </flux:text>
        </div>

        {{-- Placeholder pour l'aperçu PDF --}}
        {{-- Une fois implémenté, afficher une miniature du PDF ici --}}
    </div>

    {{-- Données extraites (placeholder) --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading size="sm" class="text-lg font-medium">
                Données extraites
            </flux:heading>
            <flux:badge color="zinc" size="sm">
                0 cours détecté
            </flux:badge>
        </div>

        {{-- État vide --}}
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.cpu-chip class="mx-auto size-12 text-zinc-400" />
            <flux:text class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                Les données extraites apparaîtront ici après le traitement du PDF.
            </flux:text>
            <flux:text class="mt-2 text-xs text-zinc-500 dark:text-zinc-500">
                Vous pourrez les vérifier et les modifier avant l'importation finale
            </flux:text>
        </div>

        {{-- Exemple de données extraites (commenté pour référence) --}}
        {{--
        <div class="space-y-2">
            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <flux:text class="font-medium">Physique Quantique</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Mardi 28/11/2025 • 14:00 - 16:00 • Salle B203
                    </flux:text>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-500">
                        Prof. Martin • TD
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:button variant="ghost" size="sm" icon="pencil" />
                    <flux:button variant="ghost" size="sm" icon="trash" />
                </div>
            </div>
        </div>
        --}}
    </div>

    {{-- Statistiques d'extraction --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                Cours détectés
            </flux:text>
            <flux:heading size="lg" class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">
                0
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
                <flux:icon.check-circle class="size-5 shrink-0 text-zinc-400" />
                <span>Vérifier que toutes les dates sont correctement extraites</span>
            </li>
            <li class="flex items-start gap-2">
                <flux:icon.check-circle class="size-5 shrink-0 text-zinc-400" />
                <span>Confirmer les heures de début et de fin de chaque cours</span>
            </li>
            <li class="flex items-start gap-2">
                <flux:icon.check-circle class="size-5 shrink-0 text-zinc-400" />
                <span>Vérifier l'orthographe des noms de matières et enseignants</span>
            </li>
            <li class="flex items-start gap-2">
                <flux:icon.check-circle class="size-5 shrink-0 text-zinc-400" />
                <span>Confirmer les numéros de salles</span>
            </li>
            <li class="flex items-start gap-2">
                <flux:icon.check-circle class="size-5 shrink-0 text-zinc-400" />
                <span>S'assurer que les types de cours (CM/TD/TP) sont corrects</span>
            </li>
        </ul>
    </div>

    {{-- Actions --}}
    <div class="flex gap-3">
        <flux:button variant="primary" icon="cpu-chip" disabled>
            Lancer l'extraction
        </flux:button>
        <flux:button variant="outline" icon="arrow-down-tray" disabled>
            Importer les données
        </flux:button>
        <flux:button variant="ghost" wire:click="resetForm">
            Réinitialiser
        </flux:button>
    </div>
</div>

