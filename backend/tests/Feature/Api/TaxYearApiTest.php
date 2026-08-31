<?php

use App\Models\TaxBracket;
use App\Models\TaxRegion;
use App\Models\TaxYear;
use App\TaxTables\BracketKind;
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

it('lists the municipalities the wizard can offer', function () {
    $response = $this->getJson('/api/tax-years/2026/municipalities')->assertOk();

    expect($response->json('data'))->toHaveCount(8);
    expect(collect($response->json('data'))->pluck('name'))->toContain('Milano', 'Roma');
    expect($response->json('data.0'))->toHaveKeys([
        'name',
        'province',
        'region',
        'rate',
        'exemptionThreshold',
    ]);
});

it('leaves out a municipality whose region has no rates', function () {
    // Offering it would end in the repository refusing to build a configuration: a dead end
    // presented to the user as a choice.
    $lazio = TaxRegion::query()->where('name', 'Lazio')->firstOrFail();
    TaxBracket::query()
        ->where('kind', BracketKind::RegionalSurtax)
        ->where('owner_id', $lazio->id)
        ->delete();

    $names = collect($this->getJson('/api/tax-years/2026/municipalities')->json('data'))->pluck('name');

    expect($names)->not->toContain('Roma');
    expect($names)->toContain('Milano');
});

it('returns 404 for a year that was never published', function () {
    $this->getJson('/api/tax-years/2099')->assertNotFound();
});

it('returns 404 for a year that exists but is not published', function () {
    TaxYear::query()->where('year', 2026)->update(['published_at' => null]);

    $this->getJson('/api/tax-years/2026')->assertNotFound();
});

it('returns 404 for a year that is not a number', function () {
    // The controller type hints `int $year`. Without the route constraint these reached it and
    // died on a TypeError, answering 500 to what is only a URL that names no year.
    $this->getJson('/api/tax-years/abc')->assertNotFound();
    $this->getJson('/api/tax-years/abc/municipalities')->assertNotFound();
});
