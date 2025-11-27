<?php

use App\Models\User;
use function Livewire\Volt\{layout, state, computed};

/**
 * Composant de gestion des utilisateurs (Admin)
 *
 * Permet aux administrateurs de voir la liste des utilisateurs,
 * gérer leurs permissions et leurs statuts.
 */

layout('components.layouts.app');

// État du composant
state([
    'search' => '',           // Recherche d'utilisateurs
    'filterRole' => 'all',    // Filtre par rôle (all, admin, user)
    'sortBy' => 'created_at', // Tri (name, email, created_at)
    'sortDirection' => 'desc', // Direction du tri
]);

/**
 * Computed property pour obtenir les utilisateurs filtrés
 * TODO: Implémenter la logique de recherche et filtrage
 */
$users = computed(function () {
    // Placeholder: retourner quelques utilisateurs de démonstration
    return collect([
        (object) [
            'id' => 1,
            'name' => 'Jean Dupont',
            'email' => 'jean.dupont@cnam.fr',
            'is_admin' => true,
            'email_verified_at' => now(),
            'created_at' => now()->subMonths(3),
        ],
        (object) [
            'id' => 2,
            'name' => 'Marie Martin',
            'email' => 'marie.martin@cnam.fr',
            'is_admin' => false,
            'email_verified_at' => now(),
            'created_at' => now()->subMonths(1),
        ],
        (object) [
            'id' => 3,
            'name' => 'Pierre Bernard',
            'email' => 'pierre.bernard@cnam.fr',
            'is_admin' => false,
            'email_verified_at' => null,
            'created_at' => now()->subDays(5),
        ],
    ]);
});

/**
 * Computed property pour les statistiques
 * TODO: Calculer depuis la base de données
 */
$stats = computed(function () {
    return [
        'total' => 156,
        'admins' => 3,
        'verified' => 142,
        'pending' => 14,
    ];
});

/**
 * Action pour promouvoir un utilisateur en admin
 * TODO: Implémenter la logique
 */
$toggleAdmin = function ($userId) {
    // Placeholder
    $this->dispatch('user-updated', userId: $userId);
};

/**
 * Action pour supprimer un utilisateur
 * TODO: Implémenter la logique avec confirmation
 */
$deleteUser = function ($userId) {
    // Placeholder
    $this->dispatch('user-deleted', userId: $userId);
};

/**
 * Action pour renvoyer l'email de vérification
 * TODO: Implémenter la logique
 */
$resendVerification = function ($userId) {
    // Placeholder
    $this->dispatch('verification-sent', userId: $userId);
};

?>

<div class="space-y-6">
    {{-- En-tête de la page --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="text-2xl font-semibold">
                Gestion des Utilisateurs
            </flux:heading>
            <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                Administrer les comptes utilisateurs de la plateforme
            </flux:text>
        </div>

        <flux:button variant="primary" icon="user-plus">
            Nouvel utilisateur
        </flux:button>
    </div>

    {{-- Statistiques --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                Total Utilisateurs
            </flux:text>
            <flux:heading size="lg" class="mt-2 text-3xl font-bold">
                {{ $this->stats['total'] }}
            </flux:heading>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                Administrateurs
            </flux:text>
            <flux:heading size="lg" class="mt-2 text-3xl font-bold text-amber-600 dark:text-amber-400">
                {{ $this->stats['admins'] }}
            </flux:heading>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                Comptes Vérifiés
            </flux:text>
            <flux:heading size="lg" class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">
                {{ $this->stats['verified'] }}
            </flux:heading>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                En Attente
            </flux:text>
            <flux:heading size="lg" class="mt-2 text-3xl font-bold text-orange-600 dark:text-orange-400">
                {{ $this->stats['pending'] }}
            </flux:heading>
        </div>
    </div>

    {{-- Filtres et recherche --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="grid gap-4 sm:grid-cols-3">
            {{-- Barre de recherche --}}
            <div class="sm:col-span-2">
                <flux:field>
                    <flux:label>Rechercher</flux:label>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Nom, email..."
                        icon="magnifying-glass"
                    />
                </flux:field>
            </div>

            {{-- Filtre par rôle --}}
            <div>
                <flux:field>
                    <flux:label>Filtrer par rôle</flux:label>
                    <flux:select wire:model.live="filterRole">
                        <option value="all">Tous les utilisateurs</option>
                        <option value="admin">Administrateurs</option>
                        <option value="user">Utilisateurs standards</option>
                    </flux:select>
                </flux:field>
            </div>
        </div>
    </div>

    {{-- Liste des utilisateurs --}}
    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Utilisateur
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Email
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Rôle
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Statut
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Inscription
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->users as $user)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                        {{ substr($user->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <flux:text class="font-medium">
                                            {{ $user->name }}
                                        </flux:text>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text class="text-sm">
                                    {{ $user->email }}
                                </flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($user->is_admin)
                                    <flux:badge color="amber" size="sm">
                                        Administrateur
                                    </flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">
                                        Utilisateur
                                    </flux:badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($user->email_verified_at)
                                    <flux:badge color="green" size="sm" icon="check-circle">
                                        Vérifié
                                    </flux:badge>
                                @else
                                    <flux:badge color="orange" size="sm" icon="exclamation-circle">
                                        En attente
                                    </flux:badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $user->created_at->format('d/m/Y') }}
                                </flux:text>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />

                                    <flux:menu>
                                        <flux:menu.item icon="pencil">
                                            Modifier
                                        </flux:menu.item>

                                        @if(!$user->is_admin)
                                            <flux:menu.item icon="shield-check" wire:click="toggleAdmin({{ $user->id }})">
                                                Promouvoir admin
                                            </flux:menu.item>
                                        @else
                                            <flux:menu.item icon="shield-exclamation" wire:click="toggleAdmin({{ $user->id }})">
                                                Révoquer admin
                                            </flux:menu.item>
                                        @endif

                                        @if(!$user->email_verified_at)
                                            <flux:menu.item icon="envelope" wire:click="resendVerification({{ $user->id }})">
                                                Renvoyer email
                                            </flux:menu.item>
                                        @endif

                                        <flux:menu.separator />

                                        <flux:menu.item icon="trash" variant="danger" wire:click="deleteUser({{ $user->id }})">
                                            Supprimer
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination placeholder --}}
        <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:text class="text-center text-sm text-zinc-600 dark:text-zinc-400">
                Affichage de 3 utilisateurs (TODO: Implémenter la pagination)
            </flux:text>
        </div>
    </div>

    {{-- Actions en masse placeholder --}}
    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading size="sm" class="mb-3 text-lg font-medium">
            Actions disponibles
        </flux:heading>
        <div class="grid gap-3 sm:grid-cols-3">
            <flux:button variant="outline" icon="envelope">
                Email en masse
            </flux:button>
            <flux:button variant="outline" icon="arrow-down-tray">
                Exporter la liste
            </flux:button>
            <flux:button variant="outline" icon="arrow-path">
                Synchroniser
            </flux:button>
        </div>
    </div>
</div>

