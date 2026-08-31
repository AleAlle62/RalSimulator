<?php

use Database\Seeders\TaxPlaces2026Seeder;
use Database\Seeders\TaxYear2026Seeder;

beforeEach(function () {
    $this->seed([TaxYear2026Seeder::class, TaxPlaces2026Seeder::class]);
});

function sharedSimulationToken(): string
{
    return test()->postJson('/api/simulations', [
        'gross_annual_salary' => 35_000,
        'monthly_payments_count' => 14,
        'sector' => 'commerce',
        'municipality' => 'Milano',
    ])->assertCreated()->json('data.token');
}

/**
 * The reason this page exists at all: a crawler unfurling a shared link reads the head and
 * leaves, so the figure has to be in the markup rather than fetched by the SPA afterwards.
 */
it('puts the net figure of this simulation in the preview tags', function () {
    $response = $this->get('/s/'.sharedSimulationToken())->assertOk();

    $response->assertSee('25.967,22 € netti su 35.000 € di RAL', false);
    $response->assertSee('<meta property="og:title" content="25.967,22 € netti su 35.000 € di RAL">', false);
    $response->assertSee('1.910,42 €', false);
});

/**
 * Shared simulations are public to whoever holds the token, which is not guessable. That is not
 * a reason to let a search engine index somebody's salary.
 */
it('asks search engines not to index a shared simulation', function () {
    $this->get('/s/'.sharedSimulationToken())
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

/**
 * The catch-all route is declared after this one and its constraint does not exclude "s" — it
 * cannot, since the constraint is anchored at the start of the path and would take /simulazione
 * with it. Declaration order is the whole guarantee, so it gets a test.
 */
it('is not shadowed by the SPA catch-all', function () {
    $this->get('/s/'.sharedSimulationToken())
        ->assertOk()
        ->assertSee('Simulazione condivisa', false);
});

it('answers 404 for a token that does not exist', function () {
    $this->get('/s/01ARZ3NDEKTSV4RRFFQ69G5FAV')->assertNotFound();
});
