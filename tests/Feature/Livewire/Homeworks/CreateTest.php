<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('homework create page can be rendered', function () {
    $response = $this->get(route('homeworks.create'));

    $response->assertSuccessful();
});

test('homework edit page can be rendered', function () {
    $homework = Event::factory()->homework()->create();

    $response = $this->get(route('homeworks.edit', $homework));

    $response->assertSuccessful();
});

test('homework can be created with valid data', function () {
    Volt::test('homeworks.create')
        ->set('form.title', 'New Homework')
        ->set('form.description', 'Homework description')
        ->set('form.subject', 'Mathématiques')
        ->set('form.due_date', now()->addWeek()->format('Y-m-d\TH:i'))
        ->set('form.priority', 'high')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('homeworks.index'));

    $homework = Event::where('title', 'New Homework')->first();

    expect($homework)->not->toBeNull()
        ->and($homework->type)->toBe('homework')
        ->and($homework->title)->toBe('New Homework')
        ->and($homework->description)->toBe('Homework description')
        ->and($homework->subject)->toBe('Mathématiques')
        ->and($homework->priority)->toBe('high')
        ->and($homework->completed)->toBeFalse()
        ->and($homework->created_by)->toBe($this->user->id);
});

test('homework requires title', function () {
    Volt::test('homeworks.create')
        ->set('form.title', '')
        ->set('form.due_date', now()->addWeek()->format('Y-m-d\TH:i'))
        ->set('form.priority', 'medium')
        ->call('save')
        ->assertHasErrors(['form.title' => 'required']);
});

test('homework requires due date', function () {
    Volt::test('homeworks.create')
        ->set('form.title', 'Test Homework')
        ->set('form.due_date', '')
        ->set('form.priority', 'medium')
        ->call('save')
        ->assertHasErrors(['form.due_date' => 'required']);
});

test('homework requires priority', function () {
    Volt::test('homeworks.create')
        ->set('form.title', 'Test Homework')
        ->set('form.due_date', now()->addWeek()->format('Y-m-d\TH:i'))
        ->set('form.priority', '')
        ->call('save')
        ->assertHasErrors(['form.priority' => 'required']);
});

test('homework validates priority values', function () {
    Volt::test('homeworks.create')
        ->set('form.title', 'Test Homework')
        ->set('form.due_date', now()->addWeek()->format('Y-m-d\TH:i'))
        ->set('form.priority', 'invalid')
        ->call('save')
        ->assertHasErrors(['form.priority']);
});

test('homework validates end time is after start time', function () {
    $startTime = now()->addWeek();
    $endTime = $startTime->copy()->subHour();

    Volt::test('homeworks.create')
        ->set('form.title', 'Test Homework')
        ->set('form.due_date', now()->addWeek()->format('Y-m-d\TH:i'))
        ->set('form.priority', 'medium')
        ->set('form.start_time', $startTime->format('Y-m-d\TH:i'))
        ->set('form.end_time', $endTime->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['form.end_time']);
});

test('homework can be updated', function () {
    $homework = Event::factory()->homework()->create([
        'title' => 'Original Title',
        'priority' => 'low',
    ]);

    Volt::test('homeworks.create', ['homework' => $homework])
        ->set('form.title', 'Updated Title')
        ->set('form.priority', 'high')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('homeworks.index'));

    $homework->refresh();

    expect($homework->title)->toBe('Updated Title')
        ->and($homework->priority)->toBe('high')
        ->and($homework->updated_by)->toBe($this->user->id);
});

test('homework create sets default values', function () {
    Volt::test('homeworks.create')
        ->assertSet('form.priority', 'medium')
        ->assertSet('form.color', '#3b82f6');
});

test('homework edit loads existing data', function () {
    $homework = Event::factory()->homework()->create([
        'title' => 'Test Homework',
        'description' => 'Test Description',
        'subject' => 'Mathématiques',
        'priority' => 'high',
    ]);

    Volt::test('homeworks.create', ['homework' => $homework])
        ->assertSet('form.title', 'Test Homework')
        ->assertSet('form.description', 'Test Description')
        ->assertSet('form.subject', 'Mathématiques')
        ->assertSet('form.priority', 'high');
});

test('homework create can be cancelled', function () {
    Volt::test('homeworks.create')
        ->call('cancel')
        ->assertRedirect(route('homeworks.index'));
});

test('homework validates color format', function () {
    Volt::test('homeworks.create')
        ->set('form.title', 'Test Homework')
        ->set('form.due_date', now()->addWeek()->format('Y-m-d\TH:i'))
        ->set('form.priority', 'medium')
        ->set('form.color', 'invalid')
        ->call('save')
        ->assertHasErrors(['form.color']);
});

test('homework accepts valid color format', function () {
    Volt::test('homeworks.create')
        ->set('form.title', 'Test Homework')
        ->set('form.due_date', now()->addWeek()->format('Y-m-d\TH:i'))
        ->set('form.priority', 'medium')
        ->set('form.color', '#FF5733')
        ->call('save')
        ->assertHasNoErrors();

    expect(Event::where('color', '#FF5733')->exists())->toBeTrue();
});

test('homework creates with optional fields', function () {
    Volt::test('homeworks.create')
        ->set('form.title', 'Test Homework')
        ->set('form.due_date', now()->addWeek()->format('Y-m-d\TH:i'))
        ->set('form.priority', 'medium')
        ->set('form.location', 'Room 101')
        ->set('form.subject', 'Physics')
        ->call('save')
        ->assertHasNoErrors();

    $homework = Event::where('title', 'Test Homework')->first();

    expect($homework->location)->toBe('Room 101')
        ->and($homework->subject)->toBe('Physics');
});

test('homework create page requires authentication', function () {
    auth()->logout();

    $response = $this->get(route('homeworks.create'));

    $response->assertRedirect(route('login'));
});

test('homework edit page requires authentication', function () {
    $homework = Event::factory()->homework()->create();

    auth()->logout();

    $response = $this->get(route('homeworks.edit', $homework));

    $response->assertRedirect(route('login'));
});
