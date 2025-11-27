# Système d'Administration - Documentation

## Vue d'ensemble

Le système d'administration permet de désigner certains utilisateurs comme administrateurs de la plateforme. Les administrateurs ont des privilèges supplémentaires pour gérer le contenu et les utilisateurs.

## Implémentation

### 1. Structure de la base de données

**Colonne ajoutée à la table `users`** :
- `is_admin` (boolean, default: false)

**Migration** : `database/migrations/2025_11_27_125446_add_is_admin_to_users_table.php`

### 2. Modèle User

**Méthode ajoutée** :
```php
public function isAdmin(): bool
{
    return $this->is_admin;
}
```

**Attribut fillable** :
- `is_admin` ajouté à `$fillable`

**Cast** :
- `is_admin` cast en `boolean`

### 3. Factory

**État ajouté à UserFactory** :
```php
User::factory()->admin()->create();
```

Cela créera un utilisateur avec `is_admin = true`.

## Utilisation

### Créer un administrateur

#### Méthode 1 : Via Artisan Command
```bash
php artisan user:make-admin user@example.com
```

#### Méthode 2 : Via Factory (tests)
```php
$admin = User::factory()->admin()->create();
```

#### Méthode 3 : Via mise à jour manuelle
```php
$user = User::find(1);
$user->update(['is_admin' => true]);
```

#### Méthode 4 : À la création
```php
User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => Hash::make('password'),
    'is_admin' => true,
]);
```

### Révoquer les privilèges d'administrateur

#### Méthode 1 : Via Artisan Command
```bash
php artisan user:revoke-admin user@example.com
```

#### Méthode 2 : Via mise à jour manuelle
```php
$user = User::find(1);
$user->update(['is_admin' => false]);
```

### Vérifier si un utilisateur est administrateur

```php
if (auth()->user()->isAdmin()) {
    // L'utilisateur est un administrateur
}

// Ou directement
if (auth()->user()->is_admin) {
    // L'utilisateur est un administrateur
}
```

## Commandes Artisan

### user:make-admin

**Description** : Désigner un utilisateur comme administrateur

**Syntaxe** :
```bash
php artisan user:make-admin {email}
```

**Arguments** :
- `email` : L'adresse email de l'utilisateur

**Exemples** :
```bash
php artisan user:make-admin john@example.com
```

**Réponses** :
- ✅ Succès : "User 'John Doe' (john@example.com) is now an administrator."
- ℹ️ Déjà admin : "User 'John Doe' (john@example.com) is already an administrator."
- ❌ Erreur : "User with email 'john@example.com' not found."

### user:revoke-admin

**Description** : Révoquer les privilèges d'administrateur d'un utilisateur

**Syntaxe** :
```bash
php artisan user:revoke-admin {email}
```

**Arguments** :
- `email` : L'adresse email de l'utilisateur

**Exemples** :
```bash
php artisan user:revoke-admin john@example.com
```

**Réponses** :
- ✅ Succès : "Administrator privileges revoked from 'John Doe' (john@example.com)."
- ℹ️ Pas admin : "User 'John Doe' (john@example.com) is not an administrator."
- ❌ Erreur : "User with email 'john@example.com' not found."

## Protection de routes

### Utilisation avec middleware

Vous pouvez créer un middleware personnalisé pour protéger les routes administrateur :

```bash
php artisan make:middleware EnsureUserIsAdmin
```

**app/Http/Middleware/EnsureUserIsAdmin.php** :
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
```

**Enregistrer dans bootstrap/app.php** :
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
    ]);
})
```

**Utiliser dans les routes** :
```php
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index']);
    // Autres routes admin...
});
```

### Utilisation avec Gates

**app/Providers/AppServiceProvider.php** :
```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('access-admin', function (User $user) {
        return $user->isAdmin();
    });
}
```

**Utiliser dans le code** :
```php
if (Gate::allows('access-admin')) {
    // L'utilisateur peut accéder à l'admin
}

// Ou avec @can dans Blade
@can('access-admin')
    <a href="/admin">Administration</a>
@endcan
```

### Utilisation avec Policies

```php
public function update(User $user, Post $post)
{
    return $user->isAdmin() || $user->id === $post->user_id;
}
```

## Interface utilisateur

### Afficher un badge admin

**Dans la navigation (exemple)** :
```blade
<flux:profile
    :name="auth()->user()->name"
    :initials="auth()->user()->initials()"
>
    @if(auth()->user()->isAdmin())
        <flux:badge color="amber" size="sm">Admin</flux:badge>
    @endif
</flux:profile>
```

### Menu conditionnel

```blade
@if(auth()->user()->isAdmin())
    <flux:navlist.item icon="shield-check" href="/admin">
        Administration
    </flux:navlist.item>
@endif
```

## Tests

### Tests unitaires/feature créés

**tests/Feature/UserAdminTest.php** :
- ✅ Les utilisateurs ne sont pas admin par défaut
- ✅ Les utilisateurs peuvent être créés comme admins
- ✅ Les utilisateurs peuvent devenir admins
- ✅ Le statut admin peut être révoqué
- ✅ is_admin est bien cast en boolean

**Exécuter les tests** :
```bash
php artisan test tests/Feature/UserAdminTest.php
```

### Exemples de tests

```php
test('admin can access admin dashboard', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful();
});

test('regular user cannot access admin dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});
```

## Cas d'usage courants

### 1. Créer le premier administrateur

Après l'installation, créer votre premier admin :

```bash
# Créer un utilisateur
php artisan tinker
>>> User::create(['name' => 'Admin', 'email' => 'admin@cnam.fr', 'password' => Hash::make('password'), 'email_verified_at' => now()])

# Le désigner comme admin
php artisan user:make-admin admin@cnam.fr
```

### 2. Interface d'administration

Créer une section admin avec des routes protégées :

```php
// routes/web.php
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::post('/users/{user}/toggle-admin', [AdminController::class, 'toggleAdmin'])
        ->name('admin.users.toggle-admin');
});
```

### 3. Tableau de bord admin

```php
// app/Http/Controllers/AdminController.php
public function dashboard()
{
    return view('admin.dashboard', [
        'totalUsers' => User::count(),
        'totalAdmins' => User::where('is_admin', true)->count(),
        'recentUsers' => User::latest()->take(10)->get(),
    ]);
}
```

### 4. Gérer les admins via interface web

```php
public function toggleAdmin(User $user)
{
    $user->update(['is_admin' => !$user->is_admin]);
    
    return back()->with('success', $user->is_admin 
        ? "User is now an administrator." 
        : "Admin privileges revoked.");
}
```

## Sécurité

### Bonnes pratiques

1. **Ne jamais exposer le statut admin dans les API publiques**
   ```php
   // Dans User model
   protected $hidden = ['password', 'is_admin', ...];
   ```

2. **Toujours vérifier les permissions côté serveur**
   - Ne jamais se fier uniquement au frontend
   - Valider dans les controllers et les policies

3. **Logger les actions administratives**
   ```php
   Log::info('User promoted to admin', [
       'admin_id' => auth()->id(),
       'user_id' => $user->id,
   ]);
   ```

4. **Empêcher la suppression du dernier admin**
   ```php
   public function destroy(User $user)
   {
       if ($user->isAdmin() && User::where('is_admin', true)->count() === 1) {
           abort(403, 'Cannot delete the last administrator.');
       }
       
       $user->delete();
   }
   ```

## Migration depuis un système existant

Si vous avez déjà un système de rôles :

```php
// Migration unique pour convertir les rôles existants
User::where('role', 'admin')->update(['is_admin' => true]);
```

## Évolutions futures possibles

### Système de rôles complet

Pour un système plus complexe, considérer :
- Package `spatie/laravel-permission`
- Rôles multiples (admin, moderator, editor, etc.)
- Permissions granulaires

### Audit trail

Tracker les actions administratives :
```bash
composer require spatie/laravel-activitylog
```

## Dépannage

### Problème : L'utilisateur n'est pas reconnu comme admin

**Solution** :
```bash
# Vérifier en base de données
php artisan tinker
>>> User::where('email', 'admin@example.com')->first()->is_admin

# Vider le cache si nécessaire
php artisan cache:clear
```

### Problème : La commande artisan ne trouve pas l'utilisateur

**Solution** :
```bash
# Lister tous les utilisateurs
php artisan tinker
>>> User::all(['id', 'name', 'email'])
```

## Résumé

✅ **Implémenté** :
- Colonne `is_admin` dans la table users
- Méthode `isAdmin()` sur le modèle User
- Factory state pour créer des admins
- Commande `user:make-admin`
- Commande `user:revoke-admin`
- Tests complets

🚀 **Prochaines étapes recommandées** :
- Créer un middleware `EnsureUserIsAdmin`
- Créer une interface d'administration
- Ajouter des gates/policies pour les actions admin
- Logger les actions administratives

---

**Créé le** : 27 novembre 2025  
**Version** : 1.0.0

