<?php

use App\Models\User;
use Filament\Facades\Filament;

/**
 * The panel and the public front end share one session cookie and one users table. That makes
 * `is_admin` the only thing standing between a self-registered visitor and the tax rates, so
 * it is worth testing rather than trusting.
 */
it('keeps an ordinary user out of the admin panel', function () {
    $user = User::factory()->create();

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

it('lets an admin into the admin panel', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});

it('denies a new account admin rights by default', function () {
    $user = User::factory()->create();

    expect($user->is_admin)->toBeFalse();
});

it('refuses to grant admin rights through mass assignment', function () {
    // A registration endpoint forwarding the whole request body must not be able to smuggle
    // the flag in, which is why `is_admin` is missing from the model's fillable attributes.
    $user = User::create([
        'name' => 'Mallory',
        'email' => 'mallory@example.com',
        'password' => 'password',
        'is_admin' => true,
    ]);

    expect($user->fresh()->is_admin)->toBeFalse();
});

it('redirects an anonymous visitor away from the panel', function () {
    $this->get('/admin')->assertRedirect();
});

it('keeps a logged in non-admin away from the tax tables', function () {
    // canAccessPanel() being correct in isolation is not the same as it actually gating a
    // route: this hits the tax years resource itself, the one screen decision #5 exists for.
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin/tax-years')->assertForbidden();
});

it('lets an admin reach the tax tables', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/tax-years')->assertSuccessful();
});
