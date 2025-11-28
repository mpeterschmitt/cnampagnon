# Event System & ICS Import

## Vue d'ensemble

Le système d'événements permet de gérer l'emploi du temps, les devoirs, les examens et autres événements calendaires de la plateforme CNAM.

## Modèle Event

### Structure de la base de données

Le modèle `Event` est générique et supporte plusieurs types d'événements :

**Types d'événements supportés :**
- `course` - Cours (CM, TD, TP)
- `homework` - Devoirs à rendre
- `exam` - Examens et contrôles
- `meeting` - Réunions et événements divers

**Champs principaux :**
- `title` - Titre de l'événement
- `description` - Description détaillée
- `start_time` / `end_time` - Dates et heures
- `subject` - Matière concernée
- `teacher` - Enseignant responsable
- `room` - Salle ou lieu
- `course_type` - Type de cours (CM, TD, TP)
- `source` - Source de l'événement (manual, ics_import, api)
- `external_id` - ID externe pour synchronisation

### Scopes disponibles

```php
Event::courses()  // Uniquement les cours
Event::homework() // Uniquement les devoirs
Event::exams()    // Uniquement les examens
Event::betweenDates($start, $end) // Entre deux dates
Event::forWeek($date) // Pour une semaine donnée
Event::upcoming() // À venir
Event::forSubject($subject) // Par matière
Event::forTeacher($teacher) // Par enseignant
```

### Factory et Seeders

Le factory `EventFactory` inclut plusieurs états :
- `course()` - Créer un cours
- `homework()` - Créer un devoir
- `exam()` - Créer un examen
- `meeting()` - Créer une réunion
- `thisWeek()` - Événement cette semaine
- `allDay()` - Événement toute la journée
- `completed()` - Devoir complété

**Usage :**
```php
Event::factory()->course()->thisWeek()->create();
Event::factory()->homework()->completed()->create();
```

## Page Emploi du Temps

### Route
`/schedule` - Accessible aux utilisateurs authentifiés

### Fonctionnalités
- Vue hebdomadaire (Lundi-Vendredi, 8h-18h)
- Navigation entre semaines
- Filtrage par matière, enseignant, type de cours
- Affichage codé par couleur selon le type
- Highlight du jour actuel

### Composant Livewire Volt
Fichier : `resources/views/livewire/schedule/index.blade.php`

**État du composant :**
```php
$selectedWeek    // Semaine affichée
$selectedSubject // Filtre matière
$selectedTeacher // Filtre enseignant
$selectedCourseType // Filtre type (CM/TD/TP)
```

## Import ICS

### Service IcsImportService

Le service `App\Services\IcsImportService` permet d'importer des fichiers ICS (iCalendar) dans le système d'événements.

**Méthodes principales :**

#### `validateIcsFile(string $filePath): array`
Valide un fichier ICS
```php
['valid' => true/false, 'error' => 'message ou null']
```

#### `parseIcsFile(string $filePath): array`
Parse un fichier ICS et retourne un aperçu
```php
[
    'events' => [...],
    'summary' => [
        'total' => 10,
        'courses' => 8,
        'exams' => 2,
        'other' => 0
    ]
]
```

#### `importEvents(array $events, int $userId, array $options): array`
Importe les événements en base de données

**Options disponibles :**
- `replace_existing` - Supprimer les anciens imports ICS avant
- `ignore_past_events` - Ne pas importer les événements passés

**Retour :**
```php
[
    'imported' => 10,
    'skipped' => 2,
    'errors' => []
]
```

### Format ICS attendu

Le service extrait automatiquement :
- **Matière** : depuis le titre ou SUMMARY
- **Type de cours** : détecte CM, TD, TP dans le titre
- **Salle** : depuis LOCATION ou depuis le titre (format "Salle XXX")
- **Enseignant** : détecte M., Mme, Prof, Dr dans le titre
- **Type d'événement** : détecte examen, devoir, réunion depuis le titre

**Exemples de titres reconnus :**
```
"Mathématiques - CM - M. Dupont - Salle A101"
"Physique TD - Prof. Martin"
"Examen de Chimie"
"Réunion de promotion"
```

### Page d'import Admin

**Route :** `/admin/import-ics` (admin uniquement)

**Fonctionnalités :**
1. Upload de fichier ICS (drag & drop ou sélection)
2. Validation automatique du fichier
3. Prévisualisation des événements avant import
4. Options d'import configurables
5. Statistiques de l'import

**Options d'import :**
- ☑️ Remplacer l'emploi du temps existant
- ☑️ Ignorer les événements passés (activé par défaut)

## Tests

### Event Model Tests
Fichier : `tests/Feature/EventTest.php`

- ✅ Création via factory
- ✅ Scopes (courses, homework, exams)
- ✅ Relations (creator, updater)
- ✅ Filtrage par dates
- ✅ Méthodes helper (isUpcoming, isFinished, etc.)
- ✅ Affichage dans la page schedule
- ✅ Filtrage dans la page schedule

### ICS Import Tests
Fichier : `tests/Feature/IcsImportTest.php`

- ✅ Accès admin à la page d'import
- ✅ Import d'événements en base
- ✅ Skip des événements passés
- ✅ Remplacement des imports existants

**Tests skippés :**
- Validation ICS (nécessite format spécifique)
- Parsing ICS (nécessite format spécifique)

Ces fonctionnalités sont testées manuellement avec de vrais fichiers ICS.

## Utilisation

### Créer des événements manuellement

```php
Event::create([
    'type' => 'course',
    'title' => 'Mathématiques',
    'subject' => 'Mathématiques',
    'teacher' => 'M. Dupont',
    'course_type' => 'CM',
    'room' => 'A101',
    'start_time' => now()->addDay()->setTime(10, 0),
    'end_time' => now()->addDay()->setTime(12, 0),
    'created_by' => auth()->id(),
]);
```

### Afficher les cours de la semaine

```php
$courses = Event::courses()
    ->forWeek(now())
    ->orderBy('start_time')
    ->get();
```

### Importer depuis un fichier ICS

1. Se connecter en tant qu'admin
2. Accéder à `/admin/import-ics`
3. Uploader un fichier .ics
4. Prévisualiser les événements
5. Configurer les options
6. Cliquer sur "Importer les événements"

### Générer des données de test

```bash
# Remplir la base avec des événements de test
php artisan db:seed --class=EventSeeder
```

Cela créera :
- 50 cours (dont 20 cette semaine)
- 15 devoirs (10 en cours, 5 complétés)
- 3 examens
- 5 réunions

## Architecture

```
app/
├── Models/
│   └── Event.php                    # Modèle principal
├── Services/
│   └── IcsImportService.php         # Service d'import ICS
database/
├── factories/
│   └── EventFactory.php             # Factory avec états
├── migrations/
│   └── 2025_11_27_221202_create_events_table.php
└── seeders/
    └── EventSeeder.php              # Seeder de test
resources/views/livewire/
├── schedule/
│   └── index.blade.php              # Page emploi du temps
└── admin/
    └── import-ics.blade.php         # Page import ICS
tests/Feature/
├── EventTest.php                    # Tests du modèle
└── IcsImportTest.php                # Tests d'import
```

## Évolutions possibles

### Court terme
- [ ] Gestion des conflits d'horaires
- [ ] Export ICS
- [ ] Notifications pour les changements
- [ ] Calendrier mensuel en plus de la vue hebdomadaire

### Moyen terme
- [ ] Événements récurrents (support complet)
- [ ] Partage de calendrier
- [ ] Import depuis d'autres sources (Google Calendar, Outlook)
- [ ] Vue personnalisée par utilisateur

### Long terme
- [ ] Gestion des absences
- [ ] Réservation de salles
- [ ] Sondages de disponibilité
- [ ] Intégration avec système de notes

## Notes techniques

### Dépendances
- `kigkonsult/icalcreator` ^2.41 - Parsing ICS

### Performance
- Index sur `start_time`, `end_time`, `type`, `external_id`
- Scope `betweenDates` optimisé pour requêtes hebdomadaires
- Eager loading recommandé : `->with(['creator', 'updater'])`

### Sécurité
- Middleware `auth` sur `/schedule`
- Middleware `auth` + `admin` sur `/admin/import-ics`
- Validation des fichiers ICS (taille max 5MB)
- Soft deletes activé sur Event

## Support

Pour toute question ou problème :
1. Vérifier les tests existants
2. Consulter ce document
3. Regarder les commentaires dans le code
4. Tester avec le seeder : `php artisan db:seed --class=EventSeeder`

