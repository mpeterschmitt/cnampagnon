<?php

declare(strict_types=1);

use App\Models\Event;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Configurer une URL de webhook fictive pour les tests
    config(['services.discord.webhook_url' => 'https://discord.com/api/webhooks/test']);
});

test('command sends reminders for homeworks due in 2 days', function () {
    Http::fake();

    $dueDate = now()->addDays(2);
    Event::factory()->homework()->create([
        'title' => 'Homework due in 2 days',
        'due_date' => $dueDate,
        'start_time' => $dueDate->copy()->addMinute(),
        'end_time' => $dueDate->copy()->addMinutes(2),
        'completed' => false,
        'priority' => 'high',
    ]);

    $this->artisan('homework:send-reminders')
        ->expectsOutput('Rappel envoyé pour : Homework due in 2 days (dans 2 jours)')
        ->assertSuccessful();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'discord.com/api/webhooks')
            && isset($request['embeds'][0]['title'])
            && str_contains($request['embeds'][0]['title'], 'Homework due in 2 days');
    });
});

test('command sends reminders for homeworks due in 1 week', function () {
    Http::fake();

    $dueDate = now()->addWeek();
    Event::factory()->homework()->create([
        'title' => 'Homework due in 1 week',
        'due_date' => $dueDate,
        'start_time' => $dueDate->copy()->addMinute(),
        'end_time' => $dueDate->copy()->addMinutes(2),
        'completed' => false,
        'priority' => 'medium',
    ]);

    $this->artisan('homework:send-reminders')
        ->assertSuccessful();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'discord.com/api/webhooks')
            && isset($request['embeds'][0]['title'])
            && str_contains($request['embeds'][0]['title'], 'Homework due in 1 week');
    });
});

test('command sends reminders for homeworks due in 2 weeks', function () {
    Http::fake();

    $dueDate = now()->addWeeks(2);
    Event::factory()->homework()->create([
        'title' => 'Homework due in 2 weeks',
        'due_date' => $dueDate,
        'start_time' => $dueDate->copy()->addMinute(),
        'end_time' => $dueDate->copy()->addMinutes(2),
        'completed' => false,
        'priority' => 'low',
    ]);

    $this->artisan('homework:send-reminders')
        ->assertSuccessful();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'discord.com/api/webhooks')
            && isset($request['embeds'][0]['title'])
            && str_contains($request['embeds'][0]['title'], 'Homework due in 2 weeks');
    });
});

test('command does not send reminders for completed homeworks', function () {
    Http::fake();

    $dueDate = now()->addDays(2);
    Event::factory()->homework()->create([
        'title' => 'Completed homework',
        'due_date' => $dueDate,
        'start_time' => $dueDate->copy()->addMinute(),
        'end_time' => $dueDate->copy()->addMinutes(2),
        'completed' => true,
    ]);

    $this->artisan('homework:send-reminders')
        ->assertSuccessful();

    Http::assertNothingSent();
});

test('command does not send reminders for homeworks outside reminder periods', function () {
    Http::fake();

    // Devoir dans 3 jours (pas dans les périodes de rappel)
    $dueDate = now()->addDays(3);
    Event::factory()->homework()->create([
        'title' => 'Homework in 3 days',
        'due_date' => $dueDate,
        'start_time' => $dueDate->copy()->addMinute(),
        'end_time' => $dueDate->copy()->addMinutes(2),
        'completed' => false,
    ]);

    $this->artisan('homework:send-reminders')
        ->expectsOutput('Aucun rappel à envoyer pour le moment.')
        ->assertSuccessful();

    Http::assertNothingSent();
});

test('command sends reminders with correct priority colors', function () {
    Http::fake();

    $dueDate = now()->addDays(2);

    // High priority (red)
    Event::factory()->homework()->create([
        'title' => 'High priority homework',
        'due_date' => $dueDate,
        'start_time' => $dueDate->copy()->addMinute(),
        'end_time' => $dueDate->copy()->addMinutes(2),
        'completed' => false,
        'priority' => 'high',
    ]);

    $this->artisan('homework:send-reminders')
        ->assertSuccessful();

    Http::assertSent(function ($request) {
        return $request['embeds'][0]['color'] === 15548997; // Red color
    });
});

test('command includes all homework details in notification', function () {
    Http::fake();

    $dueDate = now()->addDays(2);
    Event::factory()->homework()->create([
        'title' => 'Complete homework',
        'description' => 'Test description',
        'due_date' => $dueDate,
        'start_time' => $dueDate->copy()->addMinute(),
        'end_time' => $dueDate->copy()->addMinutes(2),
        'completed' => false,
        'priority' => 'high',
        'subject' => 'Mathematics',
        'teacher' => 'M. Dupont',
        'location' => 'Room 101',
    ]);

    $this->artisan('homework:send-reminders')
        ->assertSuccessful();

    Http::assertSent(function ($request) {
        $fields = $request['embeds'][0]['fields'];
        $fieldNames = array_column($fields, 'name');

        return in_array('📚 Matière', $fieldNames)
            && in_array('⏰ À rendre', $fieldNames)
            && in_array('⚠️ Priorité', $fieldNames)
            && in_array('👤 Enseignant', $fieldNames)
            && in_array('📍 Lieu', $fieldNames);
    });
});

test('command handles missing webhook url gracefully', function () {
    config(['services.discord.webhook_url' => null]);

    $this->artisan('homework:send-reminders')
        ->expectsOutput('Discord webhook URL non configurée. Ajoutez DISCORD_WEBHOOK_URL dans votre .env')
        ->assertFailed();
});

test('command sends multiple reminders when multiple homeworks match', function () {
    Http::fake();

    $dueDate = now()->addDays(2);

    // Créer 3 devoirs
    Event::factory()->homework()->count(3)->create([
        'due_date' => $dueDate,
        'start_time' => $dueDate->copy()->addMinute(),
        'end_time' => $dueDate->copy()->addMinutes(2),
        'completed' => false,
    ]);

    $this->artisan('homework:send-reminders')
        ->expectsOutput('Total : 3 rappel(s) envoyé(s).')
        ->assertSuccessful();

    Http::assertSentCount(3);
});

test('command with --all option sends notifications for all future homeworks', function () {
    Http::fake();

    // Créer des devoirs à différentes échéances futures
    $tomorrow = now()->addDay();
    $in3Days = now()->addDays(3);
    $in10Days = now()->addDays(10);
    $in30Days = now()->addDays(30);

    Event::factory()->homework()->create([
        'title' => 'Homework tomorrow',
        'due_date' => $tomorrow,
        'start_time' => $tomorrow->copy()->addMinute(),
        'end_time' => $tomorrow->copy()->addMinutes(2),
        'completed' => false,
    ]);

    Event::factory()->homework()->create([
        'title' => 'Homework in 3 days',
        'due_date' => $in3Days,
        'start_time' => $in3Days->copy()->addMinute(),
        'end_time' => $in3Days->copy()->addMinutes(2),
        'completed' => false,
    ]);

    Event::factory()->homework()->create([
        'title' => 'Homework in 10 days',
        'due_date' => $in10Days,
        'start_time' => $in10Days->copy()->addMinute(),
        'end_time' => $in10Days->copy()->addMinutes(2),
        'completed' => false,
    ]);

    Event::factory()->homework()->create([
        'title' => 'Homework in 30 days',
        'due_date' => $in30Days,
        'start_time' => $in30Days->copy()->addMinute(),
        'end_time' => $in30Days->copy()->addMinutes(2),
        'completed' => false,
    ]);

    $this->artisan('homework:send-reminders --all')
        ->expectsOutput('Mode --all activé : envoi de notifications pour tous les devoirs futurs...')
        ->expectsOutput('Total : 4 rappel(s) envoyé(s).')
        ->assertSuccessful();

    Http::assertSentCount(4);
});

test('command with --all does not send for completed homeworks', function () {
    Http::fake();

    $tomorrow = now()->addDay();

    // Devoir non complété
    Event::factory()->homework()->create([
        'title' => 'Incomplete homework',
        'due_date' => $tomorrow,
        'start_time' => $tomorrow->copy()->addMinute(),
        'end_time' => $tomorrow->copy()->addMinutes(2),
        'completed' => false,
    ]);

    // Devoir complété
    Event::factory()->homework()->create([
        'title' => 'Completed homework',
        'due_date' => $tomorrow,
        'start_time' => $tomorrow->copy()->addMinute(),
        'end_time' => $tomorrow->copy()->addMinutes(2),
        'completed' => true,
    ]);

    $this->artisan('homework:send-reminders --all')
        ->expectsOutput('Total : 1 rappel(s) envoyé(s).')
        ->assertSuccessful();

    Http::assertSentCount(1);
});

test('command with --all does not send for past homeworks', function () {
    Http::fake();

    $yesterday = now()->subDay();
    $tomorrow = now()->addDay();

    // Devoir passé
    Event::factory()->homework()->create([
        'title' => 'Past homework',
        'due_date' => $yesterday,
        'start_time' => $yesterday->copy()->addMinute(),
        'end_time' => $yesterday->copy()->addMinutes(2),
        'completed' => false,
    ]);

    // Devoir futur
    Event::factory()->homework()->create([
        'title' => 'Future homework',
        'due_date' => $tomorrow,
        'start_time' => $tomorrow->copy()->addMinute(),
        'end_time' => $tomorrow->copy()->addMinutes(2),
        'completed' => false,
    ]);

    $this->artisan('homework:send-reminders --all')
        ->expectsOutput('Total : 1 rappel(s) envoyé(s).')
        ->assertSuccessful();

    Http::assertSentCount(1);

    Http::assertSent(function ($request) {
        return str_contains($request['embeds'][0]['title'], 'Future homework');
    });
});

test('command with --all formats period labels correctly', function () {
    Http::fake();

    $tomorrow = now()->addDay();

    Event::factory()->homework()->create([
        'title' => 'Homework tomorrow',
        'due_date' => $tomorrow,
        'start_time' => $tomorrow->copy()->addMinute(),
        'end_time' => $tomorrow->copy()->addMinutes(2),
        'completed' => false,
    ]);

    $this->artisan('homework:send-reminders --all')
        ->assertSuccessful();

    Http::assertSent(function ($request) {
        return str_contains($request['embeds'][0]['footer']['text'], 'demain');
    });
});

