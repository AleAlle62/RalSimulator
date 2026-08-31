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

it('reports who the session belongs to', function () {
    $user = User::factory()->create(['name' => 'Alessio']);

    $this->actingAs($user)
        ->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('user.name', 'Alessio')
        ->assertJsonMissingPath('user.password');
});

it('refuses to name a user for a guest', function () {
    // The SPA calls this at start-up, so a guest hitting it is the ordinary case, not an error.
    $this->getJson('/api/me')->assertUnauthorized();
});

/*
|--------------------------------------------------------------------------
| Requests that carry no session
|--------------------------------------------------------------------------
|
| tests/Pest.php gives every Feature request a Referer, because Sanctum attaches a session to an
| API request only when it looks like it came from the frontend. That convenience is also what
| hid these two cases: dropping the header is not exotic, it is what any non browser client does,
| and it used to be the way past both the CSRF check and the session these endpoints assume.
|
*/

it('refuses a session-less login without telling which password was right', function () {
    User::factory()->create(['email' => 'alessio@example.com', 'password' => 'password123']);

    $right = $this->withoutHeader('Referer')->postJson('/api/login', [
        'email' => 'alessio@example.com',
        'password' => 'password123',
    ]);

    $wrong = $this->withoutHeader('Referer')->postJson('/api/login', [
        'email' => 'alessio@example.com',
        'password' => 'wrong-password',
    ]);

    // The status itself matters less than the two being the same one: 500 for the right password
    // against 422 for the wrong one was a password oracle, and an unlimited one.
    $right->assertStatus(419);
    $wrong->assertStatus(419);
    $this->assertGuest();
});

it('does not create an account when the request carries no session', function () {
    $this->withoutHeader('Referer')->postJson('/api/register', [
        'name' => 'Ghost',
        'email' => 'ghost@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(419);

    // It used to answer 500 with the row already written.
    $this->assertDatabaseMissing('users', ['email' => 'ghost@example.com']);
});

it('throttles repeated login attempts', function () {
    User::factory()->create(['email' => 'alessio@example.com', 'password' => 'password123']);

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/login', [
            'email' => 'alessio@example.com',
            'password' => "wrong-{$attempt}",
        ])->assertUnprocessable();
    }

    // Even the right password now: the limit is on attempts, not on failures, so guessing
    // cannot be resumed by happening to land on the correct one.
    $this->postJson('/api/login', [
        'email' => 'alessio@example.com',
        'password' => 'password123',
    ])->assertStatus(429);

    $this->assertGuest();
});

it('throttles repeated registrations for the same address', function () {
    $payload = [
        'name' => 'Mallory',
        'email' => 'mallory@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    // The first creates the account and the next four are refused as duplicates; what is being
    // counted is the attempt, whatever came of it.
    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/register', $payload);
    }

    $this->postJson('/api/register', $payload)->assertStatus(429);
});

it('does not choke when the email is not a string', function () {
    // The rate limiter keys on the email and runs before validation, so it sees whatever was
    // posted. Casting an array to a string there would raise a PHP warning and answer 500 to
    // what is only a malformed request.
    $this->postJson('/api/login', ['email' => [], 'password' => 'password123'])
        ->assertUnprocessable();

    $this->postJson('/api/register', [
        'name' => 'Mallory',
        'email' => ['a' => 'b'],
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertUnprocessable();
});
