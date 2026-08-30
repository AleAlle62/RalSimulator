<?php

use App\Models\Simulation;
use App\Models\TaxYear;
use App\Models\User;
use Database\Seeders\TaxPlaces2026Seeder;
use Database\Seeders\TaxYear2026Seeder;

beforeEach(function () {
    $this->seed([TaxYear2026Seeder::class, TaxPlaces2026Seeder::class]);
});

function validSimulationPayload(array $overrides = []): array
{
    return array_merge([
        'gross_annual_salary' => 35_000,
        'monthly_payments_count' => 14,
        'sector' => 'commerce',
        'municipality' => 'Milano',
    ], $overrides);
}

it('computes and saves a simulation for a guest', function () {
    $response = $this->postJson('/api/simulations', validSimulationPayload())->assertCreated();

    // The same figure verified end to end against the domain engine and the TypeScript
    // implementation it replaced.
    expect(round($response->json('data.result.netAnnualSalary'), 2))->toBe(25_967.22);
    expect($response->json('data.token'))->toHaveLength(26);

    $saved = Simulation::query()->where('token', $response->json('data.token'))->firstOrFail();
    expect($saved->user_id)->toBeNull();
});

it('rejects a monthly payments count outside 12, 13 or 14', function () {
    $this->postJson('/api/simulations', validSimulationPayload(['monthly_payments_count' => 11]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('monthly_payments_count');
});

it('rejects a sector that is not commerce or industry', function () {
    $this->postJson('/api/simulations', validSimulationPayload(['sector' => 'public']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('sector');
});

it('rejects a municipality that was never seeded', function () {
    $this->postJson('/api/simulations', validSimulationPayload(['municipality' => 'Atlantide']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('municipality');
});

it('rejects a non positive salary', function () {
    $this->postJson('/api/simulations', validSimulationPayload(['gross_annual_salary' => 0]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('gross_annual_salary');
});

it('ignores a year supplied by the client and always uses the current published one', function () {
    // The contract never accepts a year at all — this proves it, not just documents it.
    $response = $this->postJson('/api/simulations', validSimulationPayload(['year' => 1999]))
        ->assertCreated();

    expect(Simulation::query()->where('token', $response->json('data.token'))->firstOrFail()->tax_year_id)
        ->toBe(TaxYear::query()->where('year', 2026)->value('id'));
});

it('attaches the simulation to the logged in user who made it', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/simulations', validSimulationPayload())
        ->assertCreated();

    $saved = Simulation::query()->where('token', $response->json('data.token'))->firstOrFail();
    expect($saved->user_id)->toBe($user->id);
});

it('reads a saved simulation back by its token', function () {
    $token = $this->postJson('/api/simulations', validSimulationPayload())->json('data.token');

    $this->getJson("/api/simulations/{$token}")
        ->assertOk()
        ->assertJsonPath('data.municipality', 'Milano');
});

it('returns 404 for a token that does not exist', function () {
    $this->getJson('/api/simulations/01ARZ3NDEKTSV4RRFFQ69G5FAV')->assertNotFound();
});

it('requires authentication to list saved simulations', function () {
    $this->getJson('/api/me/simulations')->assertUnauthorized();
});

it('lists only the authenticated user\'s own simulations', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    $this->actingAs($owner)->postJson('/api/simulations', validSimulationPayload());
    $this->actingAs($stranger)->postJson('/api/simulations', validSimulationPayload());

    $response = $this->actingAs($owner)->getJson('/api/me/simulations')->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});

it('requires authentication to delete a saved simulation', function () {
    $token = $this->postJson('/api/simulations', validSimulationPayload())->json('data.token');
    $id = Simulation::query()->where('token', $token)->value('id');

    $this->deleteJson("/api/me/simulations/{$id}")->assertUnauthorized();
});

it('refuses to delete a simulation that belongs to someone else', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    $response = $this->actingAs($owner)->postJson('/api/simulations', validSimulationPayload());
    $id = Simulation::query()->where('token', $response->json('data.token'))->value('id');

    $this->actingAs($stranger)->deleteJson("/api/me/simulations/{$id}")->assertNotFound();

    expect(Simulation::query()->find($id))->not->toBeNull();
});

it('lets the owner delete their own simulation', function () {
    $owner = User::factory()->create();

    $response = $this->actingAs($owner)->postJson('/api/simulations', validSimulationPayload());
    $id = Simulation::query()->where('token', $response->json('data.token'))->value('id');

    $this->actingAs($owner)->deleteJson("/api/me/simulations/{$id}")->assertNoContent();

    expect(Simulation::query()->find($id))->toBeNull();
});

it('lets a signed-in user claim a simulation that has no owner', function () {
    $user = User::factory()->create();
    $token = $this->postJson('/api/simulations', validSimulationPayload())->json('data.token');

    $response = $this->actingAs($user)
        ->postJson("/api/me/simulations/{$token}/claim")
        ->assertOk();

    expect($response->json('data.mine'))->toBeTrue();
    expect($response->json('data.claimable'))->toBeFalse();
    expect(Simulation::query()->where('token', $token)->value('user_id'))->toBe($user->id);
});

it('shows a claimed simulation in the owner list', function () {
    $user = User::factory()->create();
    $token = $this->postJson('/api/simulations', validSimulationPayload())->json('data.token');

    $this->actingAs($user)->postJson("/api/me/simulations/{$token}/claim");

    expect($this->actingAs($user)->getJson('/api/me/simulations')->json('data.0.token'))
        ->toBe($token);
});

it('refuses to claim a simulation that already belongs to someone else', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    $token = $this->actingAs($owner)
        ->postJson('/api/simulations', validSimulationPayload())
        ->json('data.token');

    // 404 and not 403: the caller only ever holds a token, so naming the owner would turn a
    // guessed token into a way to probe which ones are taken.
    $this->actingAs($stranger)->postJson("/api/me/simulations/{$token}/claim")->assertNotFound();

    expect(Simulation::query()->where('token', $token)->value('user_id'))->toBe($owner->id);
});

it('requires authentication to claim a simulation', function () {
    $token = $this->postJson('/api/simulations', validSimulationPayload())->json('data.token');

    $this->postJson("/api/me/simulations/{$token}/claim")->assertUnauthorized();
});

it('reports a simulation as neither mine nor claimable to a stranger reading the link', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    $token = $this->actingAs($owner)
        ->postJson('/api/simulations', validSimulationPayload())
        ->json('data.token');

    $response = $this->actingAs($stranger)->getJson("/api/simulations/{$token}")->assertOk();

    expect($response->json('data.mine'))->toBeFalse();
    expect($response->json('data.claimable'))->toBeFalse();
});
