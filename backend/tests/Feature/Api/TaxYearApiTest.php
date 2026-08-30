<?php

use App\Models\TaxYear;
use Database\Seeders\TaxPlaces2026Seeder;
use Database\Seeders\TaxYear2026Seeder;

beforeEach(function () {
    $this->seed([TaxYear2026Seeder::class, TaxPlaces2026Seeder::class]);
});

it('returns the constants and national bands for a published year', function () {
    $response = $this->getJson('/api/tax-years/2026')->assertOk();

    $response->assertJsonPath('data.year', 2026)
        ->assertJsonCount(20, 'data.constants')
        // 3 IRPEF bands + 3 wedge cut bands: the regional and municipal ones are not national.
        ->assertJsonCount(6, 'data.brackets');

    $firstConstant = $response->json('data.constants.0');
    expect($firstConstant)->toHaveKeys(['key', 'value', 'sourceLabel', 'sourceUrl']);
    expect($firstConstant['sourceUrl'])->not->toBeNull();
});

it('does not leak a region or municipality band as national', function () {
    $kinds = collect($this->getJson('/api/tax-years/2026')->json('data.brackets'))
        ->pluck('kind')
        ->unique()
        ->values()
        ->all();

    expect($kinds)->toEqualCanonicalizing(['irpef', 'wedge_cut_exempt_bonus']);
});

it('returns 404 for a year that was never published', function () {
    $this->getJson('/api/tax-years/2099')->assertNotFound();
});

it('returns 404 for a year that exists but is not published', function () {
    TaxYear::query()->where('year', 2026)->update(['published_at' => null]);

    $this->getJson('/api/tax-years/2026')->assertNotFound();
});
