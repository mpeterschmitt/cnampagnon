<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function () {
    // Vider la config pour chaque test
    config(['auth.allowed_email_domains' => []]);
});

test('user can register with any email when no domains are configured', function () {
    config(['auth.allowed_email_domains' => []]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect('/');
    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
});

test('user can register with allowed domain', function () {
    config(['auth.allowed_email_domains' => ['example.com', 'university.edu']]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'student@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect('/');
    expect(User::where('email', 'student@example.com')->exists())->toBeTrue();
});

test('user cannot register with disallowed domain', function () {
    config(['auth.allowed_email_domains' => ['example.com', 'university.edu']]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@gmail.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
    expect(User::where('email', 'test@gmail.com')->exists())->toBeFalse();
});

test('wildcard domain allows subdomains', function () {
    config(['auth.allowed_email_domains' => ['*.university.edu']]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'student@cs.university.edu',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect('/');
    expect(User::where('email', 'student@cs.university.edu')->exists())->toBeTrue();
});

test('wildcard domain does not allow base domain', function () {
    config(['auth.allowed_email_domains' => ['*.university.edu']]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'student@university.edu',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
    expect(User::where('email', 'student@university.edu')->exists())->toBeFalse();
});

test('exact domain match is case insensitive', function () {
    config(['auth.allowed_email_domains' => ['example.com']]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@EXAMPLE.COM',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect('/');
    expect(User::where('email', 'test@EXAMPLE.COM')->exists())->toBeTrue();
});

test('multiple allowed domains work correctly', function () {
    config(['auth.allowed_email_domains' => ['example.com', 'test.org', 'school.edu']]);

    // Test premier domaine
    $response1 = $this->post('/register', [
        'name' => 'User One',
        'email' => 'user1@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);
    $response1->assertRedirect('/');

    // Test deuxième domaine
    $response2 = $this->post('/register', [
        'name' => 'User Two',
        'email' => 'user2@test.org',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);
    $response2->assertRedirect('/');

    // Test troisième domaine
    $response3 = $this->post('/register', [
        'name' => 'User Three',
        'email' => 'user3@school.edu',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);
    $response3->assertRedirect('/');

    expect(User::where('email', 'user1@example.com')->exists())->toBeTrue();
    expect(User::where('email', 'user2@test.org')->exists())->toBeTrue();
    expect(User::where('email', 'user3@school.edu')->exists())->toBeTrue();
});

test('error message shows allowed domains', function () {
    config(['auth.allowed_email_domains' => ['example.com', 'university.edu']]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@gmail.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');

    $errors = session('errors');
    $emailError = $errors->get('email')[0];

    expect($emailError)->toContain('example.com');
    expect($emailError)->toContain('university.edu');
});

test('validation works with mixed case in configuration', function () {
    config(['auth.allowed_email_domains' => ['Example.COM', 'University.EDU']]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect('/');
    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
});

test('invalid email format is rejected before domain check', function () {
    config(['auth.allowed_email_domains' => ['example.com']]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'not-an-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
});
