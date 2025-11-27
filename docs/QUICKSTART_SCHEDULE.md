# Guide de démarrage rapide - Emploi du Temps

## 🚀 Accès rapide

### URL
```
http://localhost:8000/schedule
```

### Prérequis
- Être authentifié (connexion requise)

## 📋 Ce qui fonctionne maintenant

### ✅ Interface utilisateur complète
- Page responsive avec design moderne
- Navigation dans le menu latéral (icône calendrier)
- En-tête avec titre et description
- Grille horaire (Lundi-Vendredi, 8h-18h)
- Section filtres (UI prête)
- Section changements de dernière minute
- Légende explicative

### ✅ Navigation temporelle
- Bouton "Aujourd'hui" pour revenir à la semaine actuelle
- Boutons "Semaine précédente" / "Semaine suivante"
- Affichage de la date et numéro de semaine
- Mise en évidence du jour actuel

### ✅ Filtres (structure prête)
- Filtre par matière
- Filtre par enseignant  
- Filtre par type de cours (CM/TD/TP)
- Bouton "Réinitialiser"

## 🎯 Actions immédiates possibles

### 1. Tester la page
```bash
# Démarrer le serveur
php artisan serve

# Puis visiter : http://localhost:8000/schedule
```

### 2. Exécuter les tests
```bash
php artisan test --filter=Schedule
```

### 3. Vérifier le code
```bash
# Formater le code
vendor/bin/pint

# Voir les routes
php artisan route:list
```

## 📝 Prochaine étape : Ajouter les données

### Créer les modèles
```bash
php artisan make:model Course -mfs
php artisan make:model Subject -mfs
php artisan make:model Teacher -mfs
```

### Exemple de migration pour Course
```php
Schema::create('courses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('subject_id')->constrained();
    $table->foreignId('teacher_id')->constrained();
    $table->string('type'); // CM, TD, TP
    $table->string('room');
    $table->integer('day_of_week'); // 1=Lundi, 5=Vendredi
    $table->time('start_time');
    $table->time('end_time');
    $table->timestamps();
});
```

### Exemple de seeder
```php
Course::create([
    'subject_id' => 1,
    'teacher_id' => 1,
    'type' => 'CM',
    'room' => 'A101',
    'day_of_week' => 1, // Lundi
    'start_time' => '09:00',
    'end_time' => '11:00',
]);
```

### Connecter au composant
Dans `schedule/index.blade.php`, remplacer :
```php
$courses = computed(function () {
    return collect([]);
});
```

Par :
```php
$courses = computed(function () {
    $query = Course::with(['subject', 'teacher'])
        ->whereBetween('day_of_week', [1, 5]);
    
    // Appliquer les filtres
    if ($this->selectedSubject) {
        $query->where('subject_id', $this->selectedSubject);
    }
    
    if ($this->selectedTeacher) {
        $query->where('teacher_id', $this->selectedTeacher);
    }
    
    if ($this->selectedCourseType) {
        $query->where('type', $this->selectedCourseType);
    }
    
    return $query->get();
});
```

## 📚 Fichiers importants

```
routes/web.php                              → Route /schedule
resources/views/livewire/schedule/
  └── index.blade.php                       → Composant principal
resources/views/components/layouts/app/
  └── sidebar.blade.php                     → Navigation (modifié)
tests/Feature/ScheduleTest.php              → Tests
docs/SCHEDULE.md                            → Documentation complète
IMPLEMENTATION_SUMMARY.md                   → Ce résumé
```

## 🎨 Personnalisation rapide

### Changer les horaires
Ligne ~210 dans `schedule/index.blade.php` :
```blade
@for($hour = 8; $hour <= 18; $hour++)
```

### Ajouter le samedi
Ligne ~28 dans `schedule/index.blade.php` :
```php
for ($i = 0; $i < 5; $i++) { // Changer 5 en 6
```

### Modifier les couleurs
Chercher `bg-blue-500`, `bg-green-500`, `bg-purple-500` et ajuster

## ❓ Questions fréquentes

**Q: Pourquoi la grille est vide ?**  
R: C'est normal ! Il faut d'abord créer les modèles et ajouter des données.

**Q: Comment ajouter des cours ?**  
R: Suivre la section "Prochaine étape : Ajouter les données" ci-dessus.

**Q: Les filtres ne fonctionnent pas ?**  
R: L'interface est prête, mais il faut connecter les filtres aux données (voir computed property `courses`).

**Q: Comment changer la langue en français ?**  
R: Modifier `APP_LOCALE=fr` dans `.env` et ajouter les traductions.

**Q: Puis-je voir un exemple avec des données ?**  
R: Oui ! Consulter la documentation `docs/SCHEDULE.md` section "Comment contribuer".

## 🛠️ Commandes utiles

```bash
# Voir toutes les routes
php artisan route:list

# Lancer les tests
php artisan test

# Formater le code
vendor/bin/pint

# Créer un modèle
php artisan make:model NomDuModele -mfs

# Lancer les migrations
php artisan migrate

# Lancer les seeders
php artisan db:seed
```

## ✅ Checklist de vérification

- [ ] Le serveur Laravel est lancé
- [ ] Je suis connecté avec un compte utilisateur
- [ ] Je peux accéder à `/schedule`
- [ ] La page s'affiche correctement
- [ ] La navigation entre semaines fonctionne
- [ ] Les tests passent (`php artisan test --filter=Schedule`)

## 📞 Ressources

- **Documentation complète** : `docs/SCHEDULE.md`
- **Résumé d'implémentation** : `IMPLEMENTATION_SUMMARY.md`
- **Code source** : `resources/views/livewire/schedule/index.blade.php`
- **Tests** : `tests/Feature/ScheduleTest.php`

---

**Bon développement ! 🎉**

La structure est solide et prête pour recevoir les données et la logique métier.

