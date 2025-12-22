<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Volt\Volt;

/**
 * Tests pour la page Emploi du Temps
 *
 * Ces tests vérifient que la structure de base de la page fonctionne correctement.
 * Au fur et à mesure que des fonctionnalités seront ajoutées (affichage des cours,
 * filtres, gestion des modifications), de nouveaux tests devront être ajoutés.
 */

// Test d'accès à la page (authentification requise)
test('unauthenticated users cannot access schedule page', function () {
    $this->get(route('schedule.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can access schedule page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('schedule.index'))
        ->assertSuccessful()
        ->assertSeeLivewire('schedule.index');
});

// Test de la structure de base de la page
test('schedule page displays main sections', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('schedule.index'))
        ->assertSee('Emploi du Temps')
        ->assertSee('Planning hebdomadaire des cours et activités')
        ->assertSee('Changements de dernière minute')
        ->assertSee('Légende');
});

// Test du composant Volt
test('schedule component initializes with correct default state', function () {
    $user = User::factory()->create();

    $component = Volt::actingAs($user)->test('schedule.index');

    expect($component->selectedSubject)->toBeNull();
    expect($component->selectedTeacher)->toBeNull();
    expect($component->selectedCourseType)->toBeNull();
    expect($component->viewMode)->toBe('week');
});

test('schedule component displays current week by default', function () {
    $user = User::factory()->create();

    $component = Volt::actingAs($user)->test('schedule.index');

    expect($component->selectedWeek->format('d/m/Y'))->toBe(now()->startOfWeek()->format('d/m/Y'));
});

// Test de navigation entre les semaines
test('user can navigate to previous week', function () {
    $user = User::factory()->create();

    $previousWeekStart = now()->startOfWeek()->subWeek();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('previousWeek')
        ->assertSee($previousWeekStart->format('d/m/Y'));
});

test('user can navigate to next week', function () {
    $user = User::factory()->create();

    $nextWeekStart = now()->startOfWeek()->addWeek();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('nextWeek')
        ->assertSee($nextWeekStart->format('d/m/Y'));
});

test('user can return to current week', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('nextWeek')
        ->call('nextWeek')
        ->call('currentWeek')
        ->assertSee(now()->startOfWeek()->format('d/m/Y'));
});

// Test des devoirs dans l'emploi du temps
test('schedule displays homeworks for the current week', function () {
    $user = User::factory()->create();

    $dueDate = now()->startOfWeek()->addDays(2)->setTime(10, 0);
    $homework = \App\Models\Event::factory()->homework()->create([
        'title' => 'Math Assignment',
        'due_date' => $dueDate,
        'start_time' => $dueDate->copy()->addMinute(),
        'end_time' => $dueDate->copy()->addMinutes(2),
        'priority' => 'high',
    ]);

    $this->actingAs($user)
        ->get(route('schedule.index'))
        ->assertSee('Math Assignment')
        ->assertSee('Devoirs de la semaine');
});

test('schedule does not display homeworks from other weeks', function () {
    $user = User::factory()->create();

    $dueDate = now()->subWeeks(2);
    $homework = \App\Models\Event::factory()->homework()->create([
        'title' => 'Old Assignment',
        'due_date' => $dueDate,
        'start_time' => $dueDate->copy()->addMinute(),
        'end_time' => $dueDate->copy()->addMinutes(2),
    ]);

    $this->actingAs($user)
        ->get(route('schedule.index'))
        ->assertDontSee('Old Assignment');
});

test('schedule shows overdue badge for late homeworks', function () {
    $user = User::factory()->create();

    $dueDate = now()->startOfWeek()->addDay()->subHours(2); // Yesterday within this week
    $homework = \App\Models\Event::factory()->homework()->create([
        'title' => 'Overdue Assignment',
        'due_date' => $dueDate,
        'start_time' => $dueDate->copy()->addMinute(),
        'end_time' => $dueDate->copy()->addMinutes(2),
        'completed' => false,
    ]);

    $this->actingAs($user)
        ->get(route('schedule.index'))
        ->assertSee('Retard');
});

test('schedule shows completed checkmark for finished homeworks', function () {
    $user = User::factory()->create();

    $dueDate = now()->startOfWeek()->addDay();
    $homework = \App\Models\Event::factory()->homework()->create([
        'title' => 'Completed Assignment',
        'due_date' => $dueDate,
        'start_time' => $dueDate->copy()->addMinute(),
        'end_time' => $dueDate->copy()->addMinutes(2),
        'completed' => true,
    ]);

    $this->actingAs($user)
        ->get(route('schedule.index'))
        ->assertSee('Completed Assignment');
});

// Test de l'affichage des jours de la semaine
test('schedule displays all weekdays', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('schedule.index'))
        ->assertSee(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], false);
});

// Test de la légende
test('schedule displays legend with course types', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('schedule.index'))
        ->assertSee('Cours Magistral (CM)')
        ->assertSee('Travaux Dirigés (TD)')
        ->assertSee('Travaux Pratiques (TP)');
});

// Tests pour la vue mensuelle
test('schedule component can toggle to month view', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('toggleViewMode', 'month')
        ->assertSet('viewMode', 'month')
        ->assertSee('mensuel');
});

test('schedule component can toggle back to week view', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('toggleViewMode', 'month')
        ->assertSet('viewMode', 'month')
        ->call('toggleViewMode', 'week')
        ->assertSet('viewMode', 'week')
        ->assertSee('hebdomadaire');
});

test('schedule component initializes with current month', function () {
    $user = User::factory()->create();

    $component = Volt::actingAs($user)->test('schedule.index');

    expect($component->selectedMonth->format('m/Y'))->toBe(now()->startOfMonth()->format('m/Y'));
});

test('user can navigate to previous month', function () {
    $user = User::factory()->create();

    $previousMonth = now()->startOfMonth()->subMonth();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('toggleViewMode', 'month')
        ->call('previousMonth')
        ->assertSee($previousMonth->isoFormat('MMMM YYYY'));
});

test('user can navigate to next month', function () {
    $user = User::factory()->create();

    $nextMonth = now()->startOfMonth()->addMonth();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('toggleViewMode', 'month')
        ->call('nextMonth')
        ->assertSee($nextMonth->isoFormat('MMMM YYYY'));
});

test('user can return to current month', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('toggleViewMode', 'month')
        ->call('nextMonth')
        ->call('nextMonth')
        ->call('currentMonth')
        ->assertSee(now()->isoFormat('MMMM YYYY'));
});

test('month view displays all days of the week headers', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('toggleViewMode', 'month')
        ->assertSee(['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']);
});

test('month view displays events for the current month', function () {
    $user = User::factory()->create();

    $eventDate = now()->startOfMonth()->addDays(10)->setTime(14, 0);
    $course = \App\Models\Event::factory()->course()->create([
        'title' => 'Monthly Course',
        'start_time' => $eventDate,
        'end_time' => $eventDate->copy()->addHour(),
        'course_type' => 'CM',
    ]);

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('toggleViewMode', 'month')
        ->assertSee('Monthly Course');
});

test('month view shows homework count badge when events exist', function () {
    $user = User::factory()->create();

    $dueDate = now()->startOfMonth()->addDays(5)->setTime(10, 0);
    $homework = \App\Models\Event::factory()->homework()->create([
        'title' => 'Month Homework',
        'due_date' => $dueDate,
        'start_time' => $dueDate->copy()->addMinute(),
        'end_time' => $dueDate->copy()->addMinutes(2),
    ]);

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('toggleViewMode', 'month')
        ->assertSee('Month Homework');
});

test('month view displays homeworks and exams for the month', function () {
    $user = User::factory()->create();

    $homeworkDate = now()->startOfMonth()->addDays(5)->setTime(10, 0);
    $homework = \App\Models\Event::factory()->homework()->create([
        'title' => 'Monthly Homework',
        'due_date' => $homeworkDate,
        'start_time' => $homeworkDate->copy()->addMinute(),
        'end_time' => $homeworkDate->copy()->addMinutes(2),
    ]);

    $examDate = now()->startOfMonth()->addDays(15)->setTime(14, 0);
    $exam = \App\Models\Event::factory()->exam()->create([
        'title' => 'Monthly Exam',
        'start_time' => $examDate,
        'end_time' => $examDate->copy()->addHours(2),
    ]);

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('toggleViewMode', 'month')
        ->assertSee('Devoirs du mois')
        ->assertSee('Examens du mois')
        ->assertSee('Monthly Homework')
        ->assertSee('Monthly Exam');
});

test('month view updates period label in sections', function () {
    $user = User::factory()->create();

    $dueDate = now()->startOfWeek()->addDays(2)->setTime(10, 0);
    $homework = \App\Models\Event::factory()->homework()->create([
        'title' => 'Test Homework',
        'due_date' => $dueDate,
        'start_time' => $dueDate->copy()->addMinute(),
        'end_time' => $dueDate->copy()->addMinutes(2),
    ]);

    // Test week view shows "de la semaine"
    $this->actingAs($user)
        ->get(route('schedule.index'))
        ->assertSee('Devoirs de la semaine');

    // Test month view shows "du mois"
    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('toggleViewMode', 'month')
        ->assertSee('Devoirs du mois');
});

// Test de la vue journalière
test('user can switch to day view', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('toggleViewMode', 'day')
        ->assertSet('viewMode', 'day')
        ->assertSee('Planning journalier des cours et activités');
});

test('user can navigate to previous day', function () {
    $user = User::factory()->create();

    $previousDay = now()->startOfDay()->subDay();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('toggleViewMode', 'day')
        ->call('previousDay')
        ->assertSee($previousDay->isoFormat('dddd D MMMM YYYY'));
});

test('user can navigate to next day', function () {
    $user = User::factory()->create();

    $nextDay = now()->startOfDay()->addDay();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('toggleViewMode', 'day')
        ->call('nextDay')
        ->assertSee($nextDay->isoFormat('dddd D MMMM YYYY'));
});

test('user can return to current day', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('toggleViewMode', 'day')
        ->call('nextDay')
        ->call('nextDay')
        ->call('currentDay')
        ->assertSee("Aujourd'hui");
});

test('day view displays events for selected day', function () {
    $user = User::factory()->create();

    $today = now()->startOfDay();
    $courseStart = $today->copy()->setTime(10, 0);
    $course = \App\Models\Event::factory()->course()->create([
        'title' => 'Daily Course',
        'start_time' => $courseStart,
        'end_time' => $courseStart->copy()->addHours(2),
    ]);

    $homeworkDue = $today->copy()->setTime(14, 0);
    $homework = \App\Models\Event::factory()->homework()->create([
        'title' => 'Daily Homework',
        'due_date' => $homeworkDue,
        'start_time' => $homeworkDue->copy()->addMinute(),
        'end_time' => $homeworkDue->copy()->addMinutes(2),
    ]);

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('toggleViewMode', 'day')
        ->assertSee('Daily Course')
        ->assertSee('Daily Homework');
});

test('day view updates period label in sections', function () {
    $user = User::factory()->create();

    $today = now()->startOfDay();
    $homeworkDue = $today->copy()->setTime(14, 0);
    $homework = \App\Models\Event::factory()->homework()->create([
        'title' => 'Test Homework',
        'due_date' => $homeworkDue,
        'start_time' => $homeworkDue->copy()->addMinute(),
        'end_time' => $homeworkDue->copy()->addMinutes(2),
    ]);

    Volt::actingAs($user)
        ->test('schedule.index')
        ->call('toggleViewMode', 'day')
        ->assertSee('Devoirs du jour');
});
