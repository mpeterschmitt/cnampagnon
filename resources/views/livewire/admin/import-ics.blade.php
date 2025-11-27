<?php

use function Livewire\Volt\{layout, state};

/**
 * Composant d'importation ICS vers l'emploi du temps (Admin)
 *
 * Permet aux administrateurs d'importer un fichier ICS (iCalendar)
 * pour peupler automatiquement l'emploi du temps.
 */

layout('components.layouts.app');

// État du composant
state([
    'file' => null,              // Fichier ICS uploadé
    'importing' => false,        // État d'importation en cours
    'previewData' => null,       // Aperçu des données à importer
]);

/**
 * Action pour traiter le fichier uploadé
 * TODO: Implémenter le parsing du fichier ICS
 */
$handleFileUpload = function () {
    $this->importing = true;
    // Placeholder: Simuler le traitement
    sleep(1);
    $this->importing = false;
    $this->dispatch('import-complete');
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
    $this->previewData = null;
};

?>

<div class="space-y-6">
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
                <flux:button variant="primary" icon="arrow-up-tray">
                    Choisir un fichier
                </flux:button>
            </div>

            <flux:text class="mt-4 text-xs text-zinc-500 dark:text-zinc-500">
                Formats acceptés : .ics • Taille max : 5 MB
            </flux:text>
        </div>

        {{-- TODO: Wire up file input --}}
        {{-- <input type="file" wire:model="file" accept=".ics" class="hidden" /> --}}
    </div>

    {{-- Aperçu des données (placeholder) --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="mb-4 flex items-center justify-between">
            <flux:heading size="sm" class="text-lg font-medium">
                Aperçu des événements
            </flux:heading>
            <flux:badge color="zinc" size="sm">
                0 événement détecté
            </flux:badge>
        </div>

        {{-- État vide --}}
        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.calendar class="mx-auto size-12 text-zinc-400" />
            <flux:text class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">
                Aucun fichier uploadé. Les événements apparaîtront ici une fois le fichier chargé.
            </flux:text>
        </div>

        {{-- Exemple d'aperçu (commenté pour référence) --}}
        {{--
        <div class="space-y-2">
            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <flux:text class="font-medium">Mathématiques - Cours Magistral</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Lundi 27/11/2025 • 09:00 - 11:00 • Salle A101
                    </flux:text>
                </div>
                <flux:badge color="blue" size="sm">CM</flux:badge>
            </div>
        </div>
        --}}
    </div>

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
                        Supprimer tous les cours actuels avant l'importation
                    </flux:text>
                </div>
                <flux:switch disabled />
            </div>

            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <flux:text class="font-medium">Créer les matières manquantes</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Ajouter automatiquement les nouvelles matières détectées
                    </flux:text>
                </div>
                <flux:switch disabled />
            </div>

            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <flux:text class="font-medium">Créer les enseignants manquants</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Ajouter automatiquement les nouveaux enseignants détectés
                    </flux:text>
                </div>
                <flux:switch disabled />
            </div>

            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div>
                    <flux:text class="font-medium">Ignorer les événements passés</flux:text>
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Ne pas importer les cours antérieurs à aujourd'hui
                    </flux:text>
                </div>
                <flux:switch disabled />
            </div>
        </div>
    </div>

    {{-- Historique des importations --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="sm" class="mb-4 text-lg font-medium">
            Historique des importations
        </flux:heading>

        <div class="space-y-2">
            {{-- Exemple d'historique (placeholder) --}}
            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <flux:icon.check-circle class="size-5 text-green-600" />
                    <div>
                        <flux:text class="font-medium">emploi-temps-2025.ics</flux:text>
                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                            127 événements importés • 15/11/2025 à 14:32
                        </flux:text>
                    </div>
                </div>
                <flux:button variant="ghost" size="sm" icon="arrow-path">
                    Restaurer
                </flux:button>
            </div>

            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center gap-3">
                    <flux:icon.check-circle class="size-5 text-green-600" />
                    <div>
                        <flux:text class="font-medium">planning-semestre-1.ics</flux:text>
                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                            89 événements importés • 01/09/2025 à 09:15
                        </flux:text>
                    </div>
                </div>
                <flux:button variant="ghost" size="sm" icon="arrow-path">
                    Restaurer
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex gap-3">
        <flux:button variant="primary" icon="arrow-down-tray" disabled>
            Importer les événements
        </flux:button>
        <flux:button variant="ghost" wire:click="resetForm">
            Réinitialiser
        </flux:button>
    </div>
</div>

