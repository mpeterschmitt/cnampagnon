<?php

declare(strict_types=1);

use App\Models\User;

test('users are not admin by default', function () {
    $user = User::factory()->create();

    expect($user->isAdmin())->toBeFalse();
    expect($user->is_admin)->toBeFalse();
});

test('users can be created as admins', function () {
    $user = User::factory()->admin()->create();

    expect($user->isAdmin())->toBeTrue();
    expect($user->is_admin)->toBeTrue();
});

test('users can be made admins', function () {
    $user = User::factory()->create();

    expect($user->isAdmin())->toBeFalse();

    $user->update(['is_admin' => true]);

    expect($user->fresh()->isAdmin())->toBeTrue();
});

test('admin status can be revoked', function () {
    $user = User::factory()->admin()->create();

    expect($user->isAdmin())->toBeTrue();

    $user->update(['is_admin' => false]);

    expect($user->fresh()->isAdmin())->toBeFalse();
});

test('is_admin is cast to boolean', function () {
    $user = User::factory()->create(['is_admin' => 1]);

    expect($user->is_admin)->toBeTrue();
    expect($user->is_admin)->toBeBool();

    $user = User::factory()->create(['is_admin' => 0]);

    expect($user->is_admin)->toBeFalse();
    expect($user->is_admin)->toBeBool();
});
