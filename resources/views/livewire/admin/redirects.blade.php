<?php

use App\Models\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

use function Livewire\Volt\{computed, layout, state, rules};

/**
 * Composant de gestion des liens de redirection (Admin)
 *
 * Permet aux administrateurs de créer et gérer des liens courts
 * accessibles via /s/{code}
 */
layout("components.layouts.app");

// État du composant
state([
    "search" => "",
    "filterStatus" => "all", // all, active, inactive
    "sortBy" => "created_at",
    "sortDirection" => "desc",
    "showModal" => false,
    "showClicksModal" => false,
    "viewingRedirectId" => null,
    "editingId" => null,
    "form" => [
        "code" => "",
        "url" => "",
        "title" => "",
        "description" => "",
        "is_active" => true,
    ],
]);

rules([
    "form.code" => [
        "required",
        "string",
        "max:255",
        'regex:/^[a-zA-Z0-9_-]+$/',
    ],
    "form.url" => ["required", "url", "max:2048"],
    "form.title" => ["nullable", "string", "max:255"],
    "form.description" => ["nullable", "string", "max:1000"],
    "form.is_active" => ["boolean"],
]);

/**
 * Computed property pour obtenir les redirects filtrés
 */
$redirects = computed(function () {
    $query = Redirect::query()->with("creator");

    // Recherche
    if ($this->search) {
        $query->where(function ($q) {
            $q->where("code", "like", "%{$this->search}%")
                ->orWhere("url", "like", "%{$this->search}%")
                ->orWhere("title", "like", "%{$this->search}%");
        });
    }

    // Filtre par statut
    if ($this->filterStatus === "active") {
        $query->where("is_active", true);
    } elseif ($this->filterStatus === "inactive") {
        $query->where("is_active", false);
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
        "total" => Redirect::count(),
        "active" => Redirect::where("is_active", true)->count(),
        "inactive" => Redirect::where("is_active", false)->count(),
        "total_clicks" => Redirect::sum("clicks"),
    ];
});

/**
 * Action pour ouvrir le modal de création
 */
$openCreateModal = function () {
    $this->editingId = null;
    $this->form = [
        "code" => "",
        "url" => "",
        "title" => "",
        "description" => "",
        "is_active" => true,
    ];
    $this->showModal = true;
    $this->resetValidation();
};

/**
 * Action pour ouvrir le modal d'édition
 */
$openEditModal = function ($redirectId) {
    $redirect = Redirect::findOrFail($redirectId);

    $this->editingId = $redirect->id;
    $this->form = [
        "code" => $redirect->code,
        "url" => $redirect->url,
        "title" => $redirect->title ?? "",
        "description" => $redirect->description ?? "",
        "is_active" => $redirect->is_active,
    ];

    $this->showModal = true;
    $this->resetValidation();
};

/**
 * Action pour fermer le modal
 */
$closeModal = function () {
    $this->showModal = false;
    $this->reset("form", "editingId");
    $this->resetValidation();
};

/**
 * Action pour sauvegarder (créer ou modifier)
 */
$save = function () {
    // Ajouter la règle unique dynamiquement
    $codeRules = ["required", "string", "max:255", 'regex:/^[a-zA-Z0-9_-]+$/'];
    if ($this->editingId) {
        $codeRules[] = Rule::unique("redirects", "code")->ignore(
            $this->editingId,
        );
    } else {
        $codeRules[] = "unique:redirects,code";
    }

    $this->validate([
        "form.code" => $codeRules,
        "form.url" => ["required", "url", "max:2048"],
        "form.title" => ["nullable", "string", "max:255"],
        "form.description" => ["nullable", "string", "max:1000"],
        "form.is_active" => ["boolean"],
    ]);

    $data = [
        "code" => $this->form["code"],
        "url" => $this->form["url"],
        "title" => $this->form["title"] ?? null,
        "description" => $this->form["description"] ?? null,
        "is_active" => $this->form["is_active"] ?? true,
    ];

    if ($this->editingId) {
        $redirect = Redirect::findOrFail($this->editingId);
        $redirect->update($data);
        $message = "Le lien de redirection a été mis à jour avec succès.";
    } else {
        $data["created_by"] = auth()->id();
        Redirect::create($data);
        $message = "Le lien de redirection a été créé avec succès.";
    }

    $this->closeModal();
    $this->dispatch("success", message: $message);
};

/**
 * Action pour basculer le statut actif/inactif
 */
$toggleStatus = function ($redirectId) {
    $redirect = Redirect::findOrFail($redirectId);
    $redirect->update(["is_active" => !$redirect->is_active]);

    $status = $redirect->is_active ? "activé" : "désactivé";
    $this->dispatch("success", message: "Le lien a été {$status}.");
};

/**
 * Action pour supprimer un redirect
 */
$deleteRedirect = function ($redirectId) {
    $redirect = Redirect::findOrFail($redirectId);
    $code = $redirect->code;
    $redirect->delete();

    $this->dispatch("success", message: "Le lien '{$code}' a été supprimé.");
};

/**
 * Action pour générer un code aléatoire
 */
$generateRandomCode = function () {
    do {
        $code = Str::random(6);
    } while (Redirect::where("code", $code)->exists());

    $this->form["code"] = $code;
};

/**
 * Action pour changer le tri
 */
$changeSortBy = function ($column) {
    if ($this->sortBy === $column) {
        $this->sortDirection = $this->sortDirection === "asc" ? "desc" : "asc";
    } else {
        $this->sortBy = $column;
        $this->sortDirection = "asc";
    }
};

/**
 * Action pour copier le lien dans le presse-papier
 */
$copyLink = function ($code) {
    $this->dispatch("copy-to-clipboard", code: $code);
};

/**
 * Action pour ouvrir le modal des détails de clics
 */
$viewClicks = function ($redirectId) {
    $this->viewingRedirectId = $redirectId;
    $this->showClicksModal = true;
};

/**
 * Action pour fermer le modal des clics
 */
$closeClicksModal = function () {
    $this->showClicksModal = false;
    $this->viewingRedirectId = null;
};

/**
 * Computed property pour obtenir les clics du redirect en cours de visualisation
 */
$clickDetails = computed(function () {
    if (!$this->viewingRedirectId) {
        return null;
    }

    $redirect = Redirect::with([
        "clickRecords" => function ($query) {
            $query->with("user")->orderBy("clicked_at", "desc")->limit(100);
        },
    ])->find($this->viewingRedirectId);

    if (!$redirect) {
        return null;
    }

    return [
        "redirect" => $redirect,
        "clicks" => $redirect->clickRecords,
        "unique_users" => $redirect->clickRecords
            ->where("user_id", "!=", null)
            ->unique("user_id")
            ->count(),
        "anonymous_clicks" => $redirect->clickRecords
            ->where("user_id", null)
            ->count(),
        "registered_clicks" => $redirect->clickRecords
            ->where("user_id", "!=", null)
            ->count(),
    ];
});
?>

<div class="space-y-6"
     x-data="{
         showNotification: false,
         notificationMessage: '',
         notificationType: 'success',
         copyToClipboard(code) {
             const url = window.location.origin + '/s/' + code;
             navigator.clipboard.writeText(url).then(() => {
                 this.showNotification = true;
                 this.notificationMessage = 'Lien copié dans le presse-papier !';
                 this.notificationType = 'success';
                 setTimeout(() => { this.showNotification = false; }, 3000);
             });
         }
     }"
     x-on:success.window="
         showNotification = true;
         notificationMessage = $event.detail.message;
         notificationType = 'success';
         setTimeout(() => { showNotification = false; }, 5000);
     "
     x-on:error.window="
         showNotification = true;
         notificationMessage = $event.detail.message;
         notificationType = 'error';
         setTimeout(() => { showNotification = false; }, 5000);
     "
     x-on:copy-to-clipboard.window="copyToClipboard($event.detail.code)">

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

    {{-- Modal Créer/Modifier --}}
    <flux:modal wire:model="showModal" class="w-full max-w-2xl">
        <form wire:submit="save">
            <div class="space-y-6 p-6">
                <div>
                    <flux:heading size="lg">
                        {{ $editingId ? 'Modifier le lien de redirection' : 'Créer un nouveau lien' }}
                    </flux:heading>
                </div>

                <flux:field>
                    <flux:label>Code du lien court *</flux:label>
                    <div class="flex gap-2">
                        <flux:input
                            wire:model="form.code"
                            placeholder="mon-lien"
                            class="flex-1"
                        />
                        <flux:button type="button" wire:click="generateRandomCode" variant="ghost">
                            Aléatoire
                        </flux:button>
                    </div>
                    <flux:text class="text-sm text-zinc-500">
                        Lettres, chiffres, tirets et underscores uniquement. Le lien sera : /s/{{ $form['code'] ?? 'code' }}
                    </flux:text>
                    <flux:error name="form.code" />
                </flux:field>

                <flux:field>
                    <flux:label>URL de destination *</flux:label>
                    <flux:input
                        wire:model="form.url"
                        placeholder="https://example.com/page"
                        type="url"
                    />
                    <flux:error name="form.url" />
                </flux:field>

                <flux:field>
                    <flux:label>Titre (optionnel)</flux:label>
                    <flux:input
                        wire:model="form.title"
                        placeholder="Description courte du lien"
                    />
                    <flux:error name="form.title" />
                </flux:field>

                <flux:field>
                    <flux:label>Description (optionnel)</flux:label>
                    <flux:textarea
                        wire:model="form.description"
                        placeholder="Description détaillée..."
                        rows="3"
                    />
                    <flux:error name="form.description" />
                </flux:field>

                <flux:field>
                    <flux:checkbox wire:model="form.is_active">
                        <flux:label>Actif</flux:label>
                    </flux:checkbox>
                    <flux:text class="text-sm text-zinc-500">
                        Les liens inactifs ne redirigent pas
                    </flux:text>
                </flux:field>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-zinc-200 p-6 dark:border-zinc-700">
                <flux:button type="button" wire:click="closeModal" variant="ghost">
                    Annuler
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $editingId ? 'Mettre à jour' : 'Créer' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal Détails des Clics --}}
    <flux:modal wire:model="showClicksModal" class="w-full max-w-4xl">
        @if($this->clickDetails)
            <div class="space-y-6 p-6">
                <div>
                    <flux:heading size="lg">
                        Statistiques de clics : /s/{{ $this->clickDetails['redirect']->code }}
                    </flux:heading>
                    <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                        {{ $this->clickDetails['redirect']->title ?: $this->clickDetails['redirect']->url }}
                    </flux:text>
                </div>

                {{-- Résumé des statistiques --}}
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:text class="text-xs font-medium text-zinc-600 dark:text-zinc-400">
                            Total Clics
                        </flux:text>
                        <flux:heading size="lg" class="mt-1 text-2xl font-bold">
                            {{ $this->clickDetails['redirect']->clicks }}
                        </flux:heading>
                    </div>

                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:text class="text-xs font-medium text-zinc-600 dark:text-zinc-400">
                            Utilisateurs Uniques
                        </flux:text>
                        <flux:heading size="lg" class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ $this->clickDetails['unique_users'] }}
                        </flux:heading>
                    </div>

                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <flux:text class="text-xs font-medium text-zinc-600 dark:text-zinc-400">
                            Clics Anonymes
                        </flux:text>
                        <flux:heading size="lg" class="mt-1 text-2xl font-bold text-orange-600 dark:text-orange-400">
                            {{ $this->clickDetails['anonymous_clicks'] }}
                        </flux:heading>
                    </div>
                </div>

                {{-- Liste des clics récents --}}
                <div>
                    <flux:heading size="sm" class="mb-3">
                        Clics récents (100 derniers)
                    </flux:heading>

                    <div class="max-h-96 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full">
                            <thead class="sticky top-0 border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Date/Heure
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Utilisateur
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Adresse IP
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Navigateur
                                </th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                            @forelse($this->clickDetails['clicks'] as $click)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                                    <td class="px-4 py-3 text-sm">
                                        <div class="text-zinc-900 dark:text-zinc-100">
                                            {{ $click->clicked_at->format('d/m/Y') }}
                                        </div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $click->clicked_at->format('H:i:s') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($click->user)
                                            <div class="flex items-center gap-2">
                                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                    {{ $click->user->initials() }}
                                                </span>
                                                <div>
                                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                                        {{ $click->user->name }}
                                                    </div>
                                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                        {{ $click->user->email }}
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <flux:badge variant="ghost" size="sm">Anonyme</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                        <code class="text-xs">{{ $click->ip_address ?: 'N/A' }}</code>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                        <div class="max-w-xs truncate text-xs" title="{{ $click->user_agent }}">
                                            {{ $click->user_agent ?: 'N/A' }}
                                        </div>
                                        @if($click->referer)
                                            <div class="mt-1 max-w-xs truncate text-xs text-zinc-500" title="Referrer: {{ $click->referer }}">
                                                De: {{ parse_url($click->referer, PHP_URL_HOST) }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                        Aucun clic enregistré
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end border-t border-zinc-200 p-6 dark:border-zinc-700">
                <flux:button wire:click="closeClicksModal" variant="ghost">
                    Fermer
                </flux:button>
            </div>
        @endif
    </flux:modal>

    {{-- En-tête de la page --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl" class="text-2xl font-semibold">
                Liens de Redirection
            </flux:heading>
            <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                Gérer les liens courts /s/{code}
            </flux:text>
        </div>

        <flux:button wire:click="openCreateModal" variant="primary" icon="plus">
            Nouveau lien
        </flux:button>
    </div>

    {{-- Statistiques --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                Total Liens
            </flux:text>
            <flux:heading size="lg" class="mt-2 text-3xl font-bold">
                {{ $this->stats['total'] }}
            </flux:heading>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                Actifs
            </flux:text>
            <flux:heading size="lg" class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">
                {{ $this->stats['active'] }}
            </flux:heading>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                Inactifs
            </flux:text>
            <flux:heading size="lg" class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">
                {{ $this->stats['inactive'] }}
            </flux:heading>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
                Total Clics
            </flux:text>
            <flux:heading size="lg" class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">
                {{ number_format($this->stats['total_clicks']) }}
            </flux:heading>
        </div>
    </div>

    {{-- Filtres et recherche --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <flux:field>
                    <flux:label>Rechercher</flux:label>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Code, URL, titre..."
                        icon="magnifying-glass"
                    />
                </flux:field>
            </div>

            <div>
                <flux:field>
                    <flux:label>Filtrer par statut</flux:label>
                    <flux:select wire:model.live="filterStatus">
                        <option value="all">Tous les liens</option>
                        <option value="active">Actifs uniquement</option>
                        <option value="inactive">Inactifs uniquement</option>
                    </flux:select>
                </flux:field>
            </div>
        </div>
    </div>

    {{-- Liste des redirects --}}
    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        <button wire:click="changeSortBy('code')" class="flex items-center gap-1 hover:text-zinc-700 dark:hover:text-zinc-300">
                            Code
                            @if($sortBy === 'code')
                                @if($sortDirection === 'asc')
                                    <flux:icon.chevron-up class="size-4" />
                                @else
                                    <flux:icon.chevron-down class="size-4" />
                                @endif
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Destination
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        <button wire:click="changeSortBy('clicks')" class="flex items-center gap-1 hover:text-zinc-700 dark:hover:text-zinc-300">
                            Clics
                            @if($sortBy === 'clicks')
                                @if($sortDirection === 'asc')
                                    <flux:icon.chevron-up class="size-4" />
                                @else
                                    <flux:icon.chevron-down class="size-4" />
                                @endif
                            @endif
                        </button>
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        Statut
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        <button wire:click="changeSortBy('created_at')" class="flex items-center gap-1 hover:text-zinc-700 dark:hover:text-zinc-300">
                            Créé le
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
                @forelse($this->redirects as $redirect)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50" wire:key="redirect-{{ $redirect->id }}">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <code class="rounded bg-zinc-100 px-2 py-1 text-sm font-mono text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100">
                                    /s/{{ $redirect->code }}
                                </code>
                                <button
                                    wire:click="copyLink('{{ $redirect->code }}')"
                                    class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                                    title="Copier le lien">
                                    <flux:icon.clipboard class="size-4" />
                                </button>
                            </div>
                            @if($redirect->title)
                                <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $redirect->title }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="max-w-xs truncate text-sm text-zinc-600 dark:text-zinc-400" title="{{ $redirect->url }}">
                                {{ $redirect->url }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <button
                                wire:click="viewClicks({{ $redirect->id }})"
                                class="cursor-pointer transition-colors hover:opacity-75"
                                title="Voir les détails">
                                <flux:badge variant="ghost" size="sm">
                                    {{ number_format($redirect->clicks) }}
                                </flux:badge>
                            </button>
                        </td>
                        <td class="px-6 py-4">
                            @if($redirect->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    Actif
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    Inactif
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $redirect->created_at->format('d/m/Y') }}
                            </div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $redirect->created_at->format('H:i') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button
                                    wire:click="viewClicks({{ $redirect->id }})"
                                    variant="ghost"
                                    size="sm"
                                    icon="chart-bar"
                                    title="Voir les statistiques">
                                </flux:button>
                                <flux:button
                                    wire:click="openEditModal({{ $redirect->id }})"
                                    variant="ghost"
                                    size="sm"
                                    icon="pencil"
                                    title="Modifier">
                                </flux:button>
                                <flux:button
                                    wire:click="toggleStatus({{ $redirect->id }})"
                                    variant="ghost"
                                    size="sm"
                                    title="{{ $redirect->is_active ? 'Désactiver' : 'Activer' }}">
                                    @if($redirect->is_active)
                                        <flux:icon.eye-slash class="size-4" />
                                    @else
                                        <flux:icon.eye class="size-4" />
                                    @endif
                                </flux:button>
                                <flux:button
                                    wire:click="deleteRedirect({{ $redirect->id }})"
                                    wire:confirm="Êtes-vous sûr de vouloir supprimer ce lien ?"
                                    variant="ghost"
                                    size="sm"
                                    icon="trash"
                                    title="Supprimer">
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <flux:icon.link class="size-12 text-zinc-300 dark:text-zinc-600" />
                                <flux:text class="text-zinc-500 dark:text-zinc-400">
                                    Aucun lien de redirection trouvé
                                </flux:text>
                                @if($search || $filterStatus !== 'all')
                                    <flux:button wire:click="$set('search', ''); $set('filterStatus', 'all')" variant="ghost" size="sm">
                                        Réinitialiser les filtres
                                    </flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($this->redirects->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $this->redirects->links() }}
            </div>
        @endif
    </div>
</div>
