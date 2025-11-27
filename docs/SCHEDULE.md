# Page Emploi du Temps - Documentation

## Vue d'ensemble

La page Emploi du Temps est une fonctionnalité complète pour afficher et gérer le planning hebdomadaire des cours de la promotion CNAM.

## Structure actuelle

### Fichiers créés

1. **Route** : `routes/web.php`
   - Route : `/schedule`
   - Nom : `schedule.index`
   - Middleware : `auth` (authentification requise)

2. **Composant Volt** : `resources/views/livewire/schedule/index.blade.php`
   - Composant Livewire Volt class-based
   - Gère l'état et la logique de la page

3. **Tests** : `tests/Feature/ScheduleTest.php`
   - Tests complets de la fonctionnalité
   - Couvre l'authentification, l'affichage, la navigation et les filtres

4. **Navigation** : `resources/views/components/layouts/app/sidebar.blade.php`
   - Lien ajouté dans le menu latéral
   - Icône : calendrier

## Fonctionnalités implémentées

### ✅ Structure de base
- En-tête de la page avec titre et sous-titre
- Layout responsive avec Tailwind CSS
- Navigation dans le menu latéral
- Protection par authentification

### ✅ Navigation temporelle
- Affichage de la semaine actuelle par défaut
- Boutons pour naviguer vers la semaine précédente/suivante
- Bouton "Aujourd'hui" pour revenir à la semaine actuelle
- Affichage du numéro de semaine et de l'année

### ✅ Filtres
- Filtre par matière (placeholder - à connecter à la base de données)
- Filtre par enseignant (placeholder - à connecter à la base de données)
- Filtre par type de cours (CM, TD, TP)
- Bouton pour réinitialiser tous les filtres

### ✅ Grille horaire
- En-têtes des jours de la semaine (Lundi à Vendredi)
- Mise en évidence du jour actuel
- Structure de grille avec créneaux horaires (8h00 - 18h00)
- Colonnes des heures et des jours

### ✅ Sections informatives
- Zone pour les changements de dernière minute
- Légende expliquant les codes couleur et symboles
- Information sur les futures fonctionnalités

## Fonctionnalités à implémenter

### 📋 Données des cours
- [ ] Créer le modèle `Course` avec migration
- [ ] Créer le modèle `Subject` pour les matières
- [ ] Créer le modèle `Teacher` pour les enseignants
- [ ] Implémenter les relations Eloquent
- [ ] Créer des factories et seeders pour les tests
- [ ] Charger les cours depuis la base de données

### 🎨 Affichage des cours
- [ ] Afficher les cours dans la grille horaire
- [ ] Gérer les cours de différentes durées (1h, 2h, 3h)
- [ ] Ajouter des couleurs par matière ou type de cours
- [ ] Afficher les informations : matière, salle, enseignant
- [ ] Gérer les cours qui se chevauchent

### 🔍 Filtres avancés
- [ ] Charger dynamiquement les matières depuis la base de données
- [ ] Charger dynamiquement les enseignants depuis la base de données
- [ ] Appliquer les filtres sur les cours affichés
- [ ] Ajouter des indicateurs visuels pour les filtres actifs

### 📢 Gestion des modifications
- [ ] Créer le modèle `ScheduleChange` pour les modifications
- [ ] Afficher les changements récents (annulations, changements de salle, etc.)
- [ ] Ajouter des badges visuels sur les cours modifiés
- [ ] Système de notifications pour les changements

### 📱 Fonctionnalités supplémentaires
- [ ] Export au format PDF
- [ ] Export au format iCal (pour synchronisation avec calendriers)
- [ ] Vue "jour" en complément de la vue "semaine"
- [ ] Vue "mois" pour une vision d'ensemble
- [ ] Recherche de cours spécifique
- [ ] Notes personnelles sur les cours
- [ ] Intégration avec un système de rappels/notifications

### 🔐 Permissions et rôles
- [ ] Définir les rôles (étudiant, enseignant, administrateur)
- [ ] Gestion des permissions pour modifier l'emploi du temps
- [ ] Interface d'administration pour gérer les cours

## Structure du code

### État du composant (State)

```php
state([
    'selectedWeek' => now()->startOfWeek(),  // Semaine affichée
    'selectedSubject' => null,                // Filtre matière
    'selectedTeacher' => null,                // Filtre enseignant
    'selectedCourseType' => null,             // Filtre type de cours
    'viewMode' => 'week',                     // Mode d'affichage
]);
```

### Computed Properties

- `weekDays` : Génère un tableau des 5 jours de la semaine (Lundi-Vendredi)
- `courses` : Récupère les cours filtrés (actuellement placeholder)

### Actions disponibles

- `previousWeek()` : Navigation vers la semaine précédente
- `nextWeek()` : Navigation vers la semaine suivante
- `currentWeek()` : Retour à la semaine actuelle
- `clearFilters()` : Réinitialisation de tous les filtres

## Tests

### Tests existants

1. **Authentification**
   - Vérification que les utilisateurs non authentifiés sont redirigés
   - Vérification que les utilisateurs authentifiés peuvent accéder à la page

2. **Structure de la page**
   - Vérification de l'affichage des sections principales
   - Vérification de l'affichage de la légende

3. **Composant Volt**
   - Initialisation avec l'état par défaut
   - Affichage de la semaine actuelle

4. **Navigation**
   - Navigation vers la semaine précédente
   - Navigation vers la semaine suivante
   - Retour à la semaine actuelle

5. **Filtres**
   - Application des filtres
   - Réinitialisation des filtres

6. **Affichage**
   - Affichage des jours de la semaine
   - Affichage de la légende avec les types de cours

### Tests à ajouter

- Tests pour l'affichage des cours réels
- Tests pour les changements de dernière minute
- Tests pour les exports (PDF, iCal)
- Tests de performance avec un grand nombre de cours

## Comment contribuer

### Ajouter un cours manuellement (placeholder)

Actuellement, la méthode `courses` retourne une collection vide. Pour tester l'affichage, vous pouvez modifier temporairement cette méthode dans le composant :

```php
$courses = computed(function () {
    return collect([
        [
            'id' => 1,
            'subject' => 'Mathématiques',
            'teacher' => 'M. Dupont',
            'type' => 'CM',
            'room' => 'A101',
            'start_time' => now()->setTime(9, 0),
            'end_time' => now()->setTime(11, 0),
            'color' => 'blue',
        ],
        // Ajoutez d'autres cours...
    ]);
});
```

### Créer les modèles de base de données

```bash
# Créer le modèle Course avec migration, factory et seeder
php artisan make:model Course -mfs

# Créer le modèle Subject
php artisan make:model Subject -mfs

# Créer le modèle Teacher
php artisan make:model Teacher -mfs

# Créer le modèle ScheduleChange
php artisan make:model ScheduleChange -mfs
```

## Technologies utilisées

- **Laravel 12** : Framework backend
- **Livewire 3** : Réactivité côté serveur
- **Volt** : API fonctionnelle pour Livewire
- **Flux UI** : Composants UI
- **Tailwind CSS 4** : Styling responsive
- **Pest** : Tests automatisés
- **Carbon** : Manipulation des dates

## Ressources

- [Documentation Laravel](https://laravel.com/docs)
- [Documentation Livewire](https://livewire.laravel.com)
- [Documentation Volt](https://livewire.laravel.com/docs/volt)
- [Documentation Flux UI](https://flux.laravel.com)

## Auteur

Projet développé pour la promotion d'ingénieurs du CNAM.

## Licence

Utilisation interne uniquement.

