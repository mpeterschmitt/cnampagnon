# Section Administration - Documentation

## Vue d'ensemble

La section Administration est une zone protégée de la plateforme, accessible uniquement aux utilisateurs ayant le statut d'administrateur. Elle comprend plusieurs pages pour gérer les utilisateurs et importer des emplois du temps.

## Pages disponibles

### 1. Gestion des Utilisateurs (`/admin/users`)

**Fonctionnalités actuelles (UI)** :
- ✅ Vue d'ensemble avec statistiques
  - Total utilisateurs
  - Nombre d'administrateurs
  - Comptes vérifiés
  - Comptes en attente
- ✅ Liste des utilisateurs avec :
  - Avatar généré
  - Nom et email
  - Badge de rôle (Admin/Utilisateur)
  - Statut de vérification
  - Date d'inscription
  - Menu d'actions (dropdown)
- ✅ Recherche en temps réel
- ✅ Filtre par rôle (Tous/Admin/Utilisateur)
- ✅ Actions disponibles par utilisateur :
  - Modifier
  - Promouvoir/Révoquer admin
  - Renvoyer email de vérification
  - Supprimer
- ✅ Actions en masse (boutons placeholders)

**TODO - Logique à implémenter** :
- [ ] Connexion à la base de données pour charger les utilisateurs réels
- [ ] Implémentation de la recherche
- [ ] Implémentation des filtres
- [ ] Actions de modification d'utilisateur
- [ ] Action de promotion/révocation admin
- [ ] Action de suppression avec confirmation
- [ ] Renvoi d'email de vérification
- [ ] Pagination
- [ ] Système de création d'utilisateur
- [ ] Export de la liste

### 2. Import ICS (`/admin/import-ics`)

**Fonctionnalités actuelles (UI)** :
- ✅ Guide d'utilisation détaillé
- ✅ Zone de drag & drop pour fichier ICS
- ✅ Aperçu des événements (placeholder)
- ✅ Options d'importation :
  - Remplacer l'emploi du temps existant
  - Créer les matières manquantes
  - Créer les enseignants manquants
  - Ignorer les événements passés
- ✅ Historique des importations (exemples)
- ✅ Actions d'importation

**TODO - Logique à implémenter** :
- [ ] Upload de fichier ICS
- [ ] Parsing du format iCalendar
- [ ] Extraction des événements
- [ ] Aperçu des données extraites
- [ ] Validation des données
- [ ] Mapping vers le modèle Course
- [ ] Gestion des options d'importation
- [ ] Création automatique des matières/enseignants
- [ ] Sauvegarde de l'historique
- [ ] Fonction de restauration
- [ ] Gestion des erreurs d'import

### 3. Import PDF (`/admin/import-pdf`)

**Fonctionnalités actuelles (UI)** :
- ✅ Avertissement sur l'OCR
- ✅ Guide des bonnes pratiques
- ✅ Zone de drag & drop pour fichier PDF
- ✅ Paramètres d'extraction OCR :
  - Qualité de l'OCR (rapide/standard/précis)
  - Détection automatique des colonnes
  - Correction orthographique
  - Extraction intelligente (IA)
- ✅ Aperçu du document (placeholder)
- ✅ Données extraites (placeholder)
- ✅ Statistiques d'extraction
- ✅ Guide de vérification
- ✅ Actions d'extraction et importation

**TODO - Logique à implémenter** :
- [ ] Upload de fichier PDF
- [ ] Intégration d'un moteur OCR (Tesseract, AWS Textract, Google Vision API)
- [ ] Extraction du texte du PDF
- [ ] Parsing et structuration des données
- [ ] Détection des colonnes (jours/heures)
- [ ] Extraction des informations de cours
- [ ] Calcul de la confiance de l'extraction
- [ ] Interface de correction manuelle
- [ ] Validation avant import
- [ ] Import en base de données
- [ ] Gestion des erreurs d'OCR

## Protection et sécurité

### Middleware Admin

**Fichier** : `app/Http/Middleware/EnsureUserIsAdmin.php`

Le middleware vérifie que :
1. L'utilisateur est authentifié
2. L'utilisateur a le statut `is_admin = true`

Si ces conditions ne sont pas remplies, une erreur 403 est retournée.

### Routes protégées

```php
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::redirect('/', 'admin/users');
    Volt::route('users', 'admin.users')->name('admin.users');
    Volt::route('import-ics', 'admin.import-ics')->name('admin.import-ics');
    Volt::route('import-pdf', 'admin.import-pdf')->name('admin.import-pdf');
});
```

### Navigation conditionnelle

La section "Administration" dans le menu latéral n'apparaît que pour les administrateurs :

```blade
@if(auth()->user()->isAdmin())
    <flux:navlist.group :heading="__('Administration')" class="grid">
        {{-- Liens admin --}}
    </flux:navlist.group>
@endif
```

## Tests

**Fichier** : `tests/Feature/AdminAccessTest.php`

**8 tests créés** :
1. ✅ Utilisateurs non authentifiés redirigés vers login
2. ✅ Utilisateurs standards reçoivent 403
3. ✅ Administrateurs peuvent accéder aux pages
4. ✅ Navigation admin invisible pour utilisateurs standards
5. ✅ Navigation admin visible pour administrateurs
6. ✅ Page utilisateurs affiche l'interface
7. ✅ Page import ICS affiche l'interface
8. ✅ Page import PDF affiche l'interface

**Exécuter les tests** :
```bash
php artisan test tests/Feature/AdminAccessTest.php
```

## Structure des fichiers

```
app/
  Http/
    Middleware/
      EnsureUserIsAdmin.php         → Middleware de protection
resources/
  views/
    livewire/
      admin/
        users.blade.php              → Gestion utilisateurs
        import-ics.blade.php         → Import ICS
        import-pdf.blade.php         → Import PDF
    components/
      layouts/
        app/
          sidebar.blade.php          → Navigation (modifiée)
routes/
  web.php                            → Routes admin
tests/
  Feature/
    AdminAccessTest.php              → Tests d'accès
bootstrap/
  app.php                            → Enregistrement middleware
```

## Accès rapide

### URLs
- **Utilisateurs** : `http://localhost:8000/admin/users`
- **Import ICS** : `http://localhost:8000/admin/import-ics`
- **Import PDF** : `http://localhost:8000/admin/import-pdf`

### Prérequis
- Être connecté
- Avoir le statut admin (`is_admin = true`)

### Créer un admin
```bash
php artisan user:make-admin user@example.com
```

## Prochaines étapes d'implémentation

### Priorité 1 : Gestion des utilisateurs
1. Connecter à la vraie base de données
2. Implémenter la recherche et les filtres
3. Implémenter les actions (promouvoir, supprimer, etc.)
4. Ajouter la pagination
5. Créer le formulaire de création d'utilisateur

### Priorité 2 : Import ICS
1. Installer une librairie ICS parser (ex: `sabre/vobject`)
2. Implémenter l'upload de fichier
3. Parser le fichier ICS
4. Créer les modèles Course, Subject, Teacher si nécessaire
5. Implémenter l'aperçu et la validation
6. Sauvegarder en base de données

### Priorité 3 : Import PDF
1. Choisir et intégrer un moteur OCR
2. Implémenter l'extraction de texte
3. Créer un algorithme de parsing du planning
4. Ajouter l'interface de correction manuelle
5. Implémenter la validation et l'import

## Packages recommandés

### Pour l'import ICS
```bash
composer require sabre/vobject
```

### Pour l'import PDF (OCR)
**Option 1 : Tesseract (Open Source)**
```bash
# Installation système
sudo apt-get install tesseract-ocr tesseract-ocr-fra
composer require thiagoalessio/tesseract_ocr
```

**Option 2 : Services cloud**
- AWS Textract
- Google Cloud Vision API
- Azure Computer Vision

### Pour le parsing de tableaux PDF
```bash
composer require smalot/pdfparser
```

## Personnalisation

### Modifier les statistiques utilisateurs

Dans `resources/views/livewire/admin/users.blade.php`, modifier la computed property `$stats` :

```php
$stats = computed(function () {
    return [
        'total' => User::count(),
        'admins' => User::where('is_admin', true)->count(),
        'verified' => User::whereNotNull('email_verified_at')->count(),
        'pending' => User::whereNull('email_verified_at')->count(),
    ];
});
```

### Ajouter une nouvelle page admin

1. Créer le composant Volt :
```bash
php artisan make:volt admin/new-page
```

2. Ajouter la route dans `routes/web.php` :
```php
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    // ...existing routes...
    Volt::route('new-page', 'admin.new-page')->name('admin.new-page');
});
```

3. Ajouter le lien dans la navigation (`sidebar.blade.php`) :
```blade
<flux:navlist.item icon="icon-name" :href="route('admin.new-page')" :current="request()->routeIs('admin.new-page')" wire:navigate>
    {{ __('Nouveau') }}
</flux:navlist.item>
```

## Design et UX

### Composants utilisés
- **Flux UI** pour tous les composants (buttons, badges, dropdowns, etc.)
- **Tailwind CSS** pour le layout et le styling
- **Icônes Heroicons** via Flux

### Thème
- Support du dark mode
- Couleurs cohérentes avec le reste de l'application
- Responsive design (mobile, tablette, desktop)

### Accessibilité
- Navigation au clavier
- Labels appropriés
- Contraste de couleurs respecté
- Composants Flux UI accessibles par défaut

## Sécurité - Checklist

- ✅ Routes protégées par middleware
- ✅ Vérification d'authentification
- ✅ Vérification du statut admin
- ✅ Navigation conditionnelle
- ✅ Tests d'accès complets
- ⏳ Validation des uploads de fichiers (à implémenter)
- ⏳ Sanitization des données importées (à implémenter)
- ⏳ Logs des actions administratives (à implémenter)
- ⏳ Rate limiting sur les imports (à implémenter)

## FAQ

**Q: Comment accéder à la section admin ?**
R: Il faut être connecté et avoir le statut admin. Utilisez `php artisan user:make-admin email@cfai-formation.fr` pour créer un admin.

**Q: Les pages admin sont vides, est-ce normal ?**
R: Oui, actuellement seule l'UI est implémentée. La logique (connexion DB, imports, etc.) reste à développer.

**Q: Comment ajouter une nouvelle page admin ?**
R: Suivez la section "Ajouter une nouvelle page admin" ci-dessus.

**Q: Peut-on limiter certaines fonctions aux super-admins ?**
R: Oui, vous pouvez ajouter un champ `is_super_admin` ou implémenter un système de permissions plus granulaire.

**Q: Comment logger les actions admin ?**
R: Utilisez `Log::info()` dans les actions ou intégrez `spatie/laravel-activitylog`.

## Support et contribution

- **Documentation complète** : `docs/ADMIN_SYSTEM.md` (système admin)
- **Guide rapide admin** : `ADMIN_QUICKSTART.md`
- **Documentation section admin** : Ce fichier

---

**Version** : 1.0.0 - UI Only  
**Créé le** : 27 novembre 2025  
**Statut** : ✅ Structure complète - Logique à implémenter

