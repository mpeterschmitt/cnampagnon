<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\User;

test('event can be created with factory', function () {
    $event = Event::factory()->create();

    expect($event)->toBeInstanceOf(Event::class)
        ->and($event->type)->toBe('course')
        ->and($event->title)->not->toBeEmpty()
        ->and($event->start_time)->not->toBeNull()
        ->and($event->end_time)->not->toBeNull();
});

test('event scopes work correctly', function () {
    Event::factory()->course()->create();
    Event::factory()->homework()->create();
    Event::factory()->exam()->create();

    expect(Event::courses()->count())->toBe(1)
        ->and(Event::homework()->count())->toBe(1)
        ->and(Event::exams()->count())->toBe(1);
});

test('event relationships work', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['created_by' => $user->id]);

    expect($event->creator)->toBeInstanceOf(User::class)
        ->and($event->creator->id)->toBe($user->id);
});

test('event between dates scope filters correctly', function () {
    $now = now();

    // Créer un événement dans la semaine actuelle
    Event::factory()->create([
        'start_time' => $now->copy()->startOfWeek()->addHours(10),
        'end_time' => $now->copy()->startOfWeek()->addHours(12),
    ]);

    // Créer un événement en dehors de la semaine
    Event::factory()->create([
        'start_time' => $now->copy()->subWeek()->addHours(10),
        'end_time' => $now->copy()->subWeek()->addHours(12),
    ]);

    $eventsThisWeek = Event::betweenDates(
        $now->copy()->startOfWeek(),
        $now->copy()->endOfWeek()
    )->get();

    expect($eventsThisWeek)->toHaveCount(1);
});

test('event helper methods work correctly', function () {
    $event = Event::factory()->course()->create([
        'start_time' => now()->addHour(),
        'end_time' => now()->addHours(3),
    ]);

    expect($event->isCourse())->toBeTrue()
        ->and($event->isHomework())->toBeFalse()
        ->and($event->isExam())->toBeFalse()
        ->and($event->getDurationInMinutes())->toBe(120)
        ->and($event->isUpcoming())->toBeTrue()
        ->and($event->isFinished())->toBeFalse();
});

test('schedule page displays events', function () {
    $user = User::factory()->create();

    // Créer des événements pour cette semaine
    Event::factory()
        ->count(5)
        ->course()
        ->thisWeek()
        ->create();

    $this->actingAs($user)
        ->get(route('schedule.index'))
        ->assertSuccessful()
        ->assertSee('Emploi du Temps');
});

test('schedule page filters by subject', function () {
    $user = User::factory()->create();

    Event::factory()->create([
        'type' => 'course',
        'subject' => 'Mathématiques',
        'teacher' => 'M. Dupont',
        'course_type' => 'CM',
        'start_time' => now()->startOfWeek()->addHours(10),
        'end_time' => now()->startOfWeek()->addHours(12),
    ]);

    Event::factory()->create([
        'type' => 'course',
        'subject' => 'Physique',
        'teacher' => 'Mme Martin',
        'course_type' => 'TD',
        'start_time' => now()->startOfWeek()->addHours(14),
        'end_time' => now()->startOfWeek()->addHours(16),
    ]);

    $this->actingAs($user)
        ->get(route('schedule.index'))
        ->assertSee('Mathématiques')
        ->assertSee('Physique');

    // Test filter dropdown has correct options
    $this->actingAs($user)
        ->get(route('schedule.index'))
        ->assertSee('Mathématiques')
        ->assertSee('M. Dupont');
});

test('homework events can be marked as completed', function () {
    $homework = Event::factory()->homework()->create([
        'completed' => false,
    ]);

    expect($homework->completed)->toBeFalse();

    $homework->update(['completed' => true]);

    expect($homework->fresh()->completed)->toBeTrue();
});
