<?php

use App\Models\User;

use function Livewire\Volt\computed;
use function Livewire\Volt\layout;
use function Livewire\Volt\state;

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
 */
$users = computed(function () {
    $query = User::query();

    // Recherche par nom ou email
    if ($this->search) {
        $query->where(function ($q) {
            $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%");
        });
    }

    // Filtre par rôle
    if ($this->filterRole === 'admin') {
        $query->where('is_admin', true);
    } elseif ($this->filterRole === 'user') {
        $query->where('is_admin', false);
    }

    // Tri
    $query->orderBy($this->sortBy, $this->sortDirection);

    return $query->paginate(15);
});

/**
 * Computed property pour les statistiques
 */
$stats = computed(function () {
    return [
        'total' => User::count(),
        'admins' => User::where('is_admin', true)->count(),
        'verified' => User::whereNotNull('email_verified_at')->count(),
        'pending' => User::whereNull('email_verified_at')->count(),
    ];
});

/**
 * Action pour promouvoir/révoquer un utilisateur en admin
 */
$toggleAdmin = function ($userId) {
    $user = User::findOrFail($userId);

    // Empêcher de se retirer soi-même les droits admin
    if ($user->id === auth()->id() && $user->is_admin) {
        $this->dispatch('error', message: 'Vous ne pouvez pas retirer vos propres privilèges administrateur.');

        return;
    }

    // Empêcher de supprimer le dernier admin
    if ($user->is_admin && User::where('is_admin', true)->count() === 1) {
        $this->dispatch('error', message: 'Impossible de révoquer le dernier administrateur.');

        return;
    }

    $user->update(['is_admin' => ! $user->is_admin]);

    $message = $user->is_admin
        ? "L'utilisateur {$user->name} est maintenant administrateur."
        : "Les privilèges administrateur ont été révoqués pour {$user->name}.";

    $this->dispatch('success', message: $message);
};

/**
 * Action pour supprimer un utilisateur
 */
$deleteUser = function ($userId) {
    $user = User::findOrFail($userId);

    // Empêcher de se supprimer soi-même
    if ($user->id === auth()->id()) {
        $this->dispatch('error', message: 'Vous ne pouvez pas supprimer votre propre compte.');

        return;
    }

    // Empêcher de supprimer le dernier admin
    if ($user->is_admin && User::where('is_admin', true)->count() === 1) {
        $this->dispatch('error', message: 'Impossible de supprimer le dernier administrateur.');

        return;
    }

    $userName = $user->name;
    $user->delete();

    $this->dispatch('success', message: "L'utilisateur {$userName} a été supprimé.");
};

/**
 * Action pour renvoyer l'email de vérification
 */
$resendVerification = function ($userId) {
    $user = User::findOrFail($userId);

    if ($user->email_verified_at) {
        $this->dispatch('error', message: 'Ce compte est déjà vérifié.');

        return;
    }

    // Envoyer l'email de vérification
    $user->sendEmailVerificationNotification();

    $this->dispatch('success', message: "Email de vérification envoyé à {$user->email}.");
};

/**
 * Action pour changer le tri
 */
$changeSortBy = function ($column) {
    if ($this->sortBy === $column) {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        $this->sortBy = $column;
        $this->sortDirection = 'asc';
    }
};

?>

<div class="space-y-6"
     x-data="{
         showNotification: false,
         notificationMessage: '',
         notificationType: 'success'
     }"
     x-on:success.window="
         showNotification = true;
         notificationMessage = $event.detail.message;
         notificationType = 'success';
         setTimeout(function() { showNotification = false; }, 5000);
     "
     x-on:error.window="
         showNotification = true;
         notificationMessage = $event.detail.message;
         notificationType = 'error';
         setTimeout(function() { showNotification = false; }, 5000);
     ">
    {{-- Notification Toast --}}
    <div x-show="showNotification"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed right-4 top-4 z-50 max-w-md rounded-lg border p-4 shadow-lg"
         :class="{
             'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950/30': notificationType === 'success',
             'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/30': notificationType === 'error'
         }"
         style="display: none;">
        <div class="flex items-start gap-3">
            <flux:icon.check-circle x-show="notificationType === 'success'" class="size-6 shrink-0 text-green-600 dark:text-green-400" />
            <flux:icon.x-circle x-show="notificationType === 'error'" class="size-6 shrink-0 text-red-600 dark:text-red-400" />
            <div class="flex-1">
                <p x-text="notificationMessage"
                   :class="{
                       'text-green-800 dark:text-green-200': notificationType === 'success',
                       'text-red-800 dark:text-red-200': notificationType === 'error'
                   }"></p>
            </div>
            <button @click="showNotification = false" class="text-zinc-400 hover:text-zinc-600">
                <flux:icon.x-mark class="size-5" />
            </button>
        </div>
    </div>

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
                        <button wire:click="changeSortBy('name')" class="flex items-center gap-1 hover:text-zinc-700 dark:hover:text-zinc-300">
                            Utilisateur
                            @if($sortBy === 'name')
                                @if($sortDirection === 'asc')
                                    <flux:icon.chevron-up class="size-4" />
                                @else
                                    <flux:icon.chevron-down class="size-4" />
                                @endif
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        <button wire:click="changeSortBy('email')" class="flex items-center gap-1 hover:text-zinc-700 dark:hover:text-zinc-300">
                            Email
                            @if($sortBy === 'email')
                                @if($sortDirection === 'asc')
                                    <flux:icon.chevron-up class="size-4" />
                                @else
                                    <flux:icon.chevron-down class="size-4" />
                                @endif
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Rôle
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Statut
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        <button wire:click="changeSortBy('created_at')" class="flex items-center gap-1 hover:text-zinc-700 dark:hover:text-zinc-300">
                            Inscription
                            @if($sortBy === 'created_at')
                                @if($sortDirection === 'asc')
                                    <flux:icon.chevron-up class="size-4" />
                                @else
                                    <flux:icon.chevron-down class="size-4" />
                                @endif
                            @endif
                        </button>
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

        {{-- Pagination --}}
        <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
            {{ $this->users->links() }}
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

