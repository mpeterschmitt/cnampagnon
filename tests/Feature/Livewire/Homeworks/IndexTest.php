<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('homework index page can be rendered', function () {
    $response = $this->get(route('homeworks.index'));

    $response->assertSuccessful();
});

test('homework index displays homeworks', function () {
    $homework = Event::factory()->homework()->create([
        'title' => 'Test Homework',
        'due_date' => now()->addWeek(),
    ]);

    Volt::test('homeworks.index')
        ->assertSee('Test Homework')
        ->assertSee('Devoirs');
});

test('homework index filters by status', function () {
    Event::factory()->homework()->create([
        'title' => 'Incomplete Homework',
        'completed' => false,
    ]);

    Event::factory()->homework()->create([
        'title' => 'Completed Homework',
        'completed' => true,
    ]);

    Volt::test('homeworks.index')
        ->set('filter', 'incomplete')
        ->assertSee('Incomplete Homework')
        ->assertDontSee('Completed Homework');
});

test('homework index filters by subject', function () {
    Event::factory()->homework()->create([
        'title' => 'Math Homework',
        'subject' => 'Mathématiques',
    ]);

    Event::factory()->homework()->create([
        'title' => 'Physics Homework',
        'subject' => 'Physique',
    ]);

    Volt::test('homeworks.index')
        ->set('selectedSubject', 'Mathématiques')
        ->assertSee('Math Homework')
        ->assertDontSee('Physics Homework');
});

test('homework index filters by priority', function () {
    Event::factory()->homework()->create([
        'title' => 'High Priority Homework',
        'priority' => 'high',
    ]);

    Event::factory()->homework()->create([
        'title' => 'Low Priority Homework',
        'priority' => 'low',
    ]);

    Volt::test('homeworks.index')
        ->set('selectedPriority', 'high')
        ->assertSee('High Priority Homework')
        ->assertDontSee('Low Priority Homework');
});

test('homework index searches by title', function () {
    Event::factory()->homework()->create([
        'title' => 'Math Assignment',
    ]);

    Event::factory()->homework()->create([
        'title' => 'Physics Lab',
    ]);

    Volt::test('homeworks.index')
        ->set('search', 'Math')
        ->assertSee('Math Assignment')
        ->assertDontSee('Physics Lab');
});

test('homework can be toggled as completed', function () {
    $homework = Event::factory()->homework()->create([
        'completed' => false,
    ]);

    Volt::test('homeworks.index')
        ->call('toggleCompleted', $homework->id);

    expect($homework->fresh()->completed)->toBeTrue();
});

test('homework can be deleted', function () {
    $homework = Event::factory()->homework()->create();

    Volt::test('homeworks.index')
        ->call('delete', $homework->id);

    expect(Event::find($homework->id))->toBeNull();
});

test('homework index resets filters', function () {
    Volt::test('homeworks.index')
        ->set('filter', 'completed')
        ->set('selectedSubject', 'Math')
        ->set('selectedPriority', 'high')
        ->set('search', 'test')
        ->call('resetFilters')
        ->assertSet('filter', 'all')
        ->assertSet('selectedSubject', null)
        ->assertSet('selectedPriority', null)
        ->assertSet('search', '');
});

test('homework index shows statistics', function () {
    Event::factory()->homework()->count(3)->create(['completed' => false]);
    Event::factory()->homework()->count(2)->create(['completed' => true]);

    Volt::test('homeworks.index')
        ->assertSee('5') // Total
        ->assertSee('2') // Completed
        ->assertSee('3'); // Incomplete
});

test('homework index shows overdue badge', function () {
    Event::factory()->homework()->create([
        'title' => 'Overdue Homework',
        'due_date' => now()->subDay(),
        'completed' => false,
    ]);

    Volt::test('homeworks.index')
        ->assertSee('En retard');
});

test('homework index requires authentication', function () {
    auth()->logout();

    $response = $this->get(route('homeworks.index'));

    $response->assertRedirect(route('login'));
});
