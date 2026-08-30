<?php

use App\Models\User;

/**
 * Session based, same-origin: no token ever appears in a response body, and every assertion
 * here is really about the session, not about JSON shape.
 */
it('registers an account and logs it in immediately', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Alessio',
        'email' => 'alessio@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated();
    $this->assertAuthenticated();
    expect(User::query()->where('email', 'alessio@example.com')->exists())->toBeTrue();
});

it('never returns the password on register', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Alessio',
        'email' => 'alessio@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertJsonMissingPath('user.password');
});

it('refuses to register two accounts with the same email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/register', [
        'name' => 'Mallory',
        'email' => 'taken@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('refuses registration when the password confirmation does not match', function () {
    $this->postJson('/api/register', [
        'name' => 'Alessio',
        'email' => 'alessio@example.com',
        'password' => 'password123',
        'password_confirmation' => 'somethingelse',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');
});

it('logs in with the right credentials', function () {
    User::factory()->create(['email' => 'alessio@example.com', 'password' => 'password123']);

    $this->postJson('/api/login', [
        'email' => 'alessio@example.com',
        'password' => 'password123',
    ])->assertOk();

    $this->assertAuthenticated();
});

it('refuses login with the wrong password', function () {
    User::factory()->create(['email' => 'alessio@example.com', 'password' => 'password123']);

    $this->postJson('/api/login', [
        'email' => 'alessio@example.com',
        'password' => 'wrong-password',
    ])->assertUnprocessable();

    $this->assertGuest();
});

it('logs out an authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/logout')
        ->assertNoContent();

    // Explicitly 'web': the auth:sanctum middleware on /api/logout calls Auth::shouldUse()
    // as a side effect of authenticating the request, which leaves 'sanctum' as the default
    // guard for the rest of this test process. A real request never carries that over — each
    // one boots a fresh default — so this is a test-only artifact, not app behaviour.
    $this->assertGuest('web');
});

it('refuses logout for a guest', function () {
    $this->postJson('/api/logout')->assertUnauthorized();
});
