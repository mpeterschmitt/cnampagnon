# Guide Rapide - Système Admin

## 🚀 Usage immédiat

### Rendre un utilisateur administrateur
```bash
php artisan user:make-admin user@example.com
```

### Révoquer les privilèges admin
```bash
php artisan user:revoke-admin user@example.com
```

### Vérifier si un utilisateur est admin (dans le code)
```php
auth()->user()->isAdmin()  // true ou false
```

## 📋 Créer votre premier admin

### Étape 1 : Créer un compte utilisateur
Inscription normale via `/register`

### Étape 2 : Le désigner comme admin
```bash
php artisan user:make-admin votre@email.com
```

### Étape 3 : Vérifier
```bash
php artisan tinker
>>> User::where('email', 'votre@email.com')->first()->is_admin
=> true
```

## 🔐 Protéger des routes (optionnel)

### Créer un middleware admin
```bash
php artisan make:middleware EnsureUserIsAdmin
```

**app/Http/Middleware/EnsureUserIsAdmin.php** :
```php
public function handle(Request $request, Closure $next)
{
    if (!auth()->check() || !auth()->user()->isAdmin()) {
        abort(403);
    }
    return $next($request);
}
```

**Enregistrer dans bootstrap/app.php** :
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias(['admin' => EnsureUserIsAdmin::class]);
})
```

**Utiliser** :
```php
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', ...);
});
```

## 🎨 Interface utilisateur

### Afficher un badge admin
```blade
@if(auth()->user()->isAdmin())
    <flux:badge color="amber">Admin</flux:badge>
@endif
```

### Menu conditionnel
```blade
@if(auth()->user()->isAdmin())
    <flux:navlist.item icon="shield-check" href="/admin">
        Administration
    </flux:navlist.item>
@endif
```

## 🧪 Tests

### Créer un admin dans les tests
```php
$admin = User::factory()->admin()->create();
```

### Tester l'accès admin
```php
test('admin can access admin page', function () {
    $admin = User::factory()->admin()->create();
    
    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful();
});
```

## 📚 Documentation complète

Voir `docs/ADMIN_SYSTEM.md` pour la documentation complète.

## ✅ Ce qui est inclus

- ✅ Colonne `is_admin` dans la base de données
- ✅ Méthode `isAdmin()` sur User
- ✅ Commande `user:make-admin`
- ✅ Commande `user:revoke-admin`
- ✅ Factory state `admin()`
- ✅ Tests automatisés
- ✅ Cast boolean automatique

## 🎯 Prochaines étapes

1. Créer votre premier admin
2. Créer un middleware de protection (si nécessaire)
3. Ajouter des sections admin dans votre interface
4. Protéger les routes sensibles

---

**Quick win** : Rendez-vous admin maintenant !
```bash
php artisan user:make-admin votre@email.com
```

