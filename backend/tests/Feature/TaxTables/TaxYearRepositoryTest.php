<?php

use App\Domain\Payslips\PayslipsCalculator;
use App\Domain\SalaryCalculator;
use App\Domain\SalaryInput;
use App\Domain\Sector;
use App\Domain\Tax\Contributions\ContributionsCalculator;
use App\Domain\Tax\Irpef\IrpefCalculator;
use App\Domain\Tax\Reliefs\ReliefsCalculator;
use App\Domain\Tax\Surtaxes\SurtaxesCalculator;
use App\Domain\Tax\TaxYear2026;
use App\Models\TaxConstant;
use App\Models\TaxYear;
use App\TaxTables\MissingTaxDataException;
use App\TaxTables\TaxConstantKey;
use App\TaxTables\TaxYearRepository;
use Database\Seeders\TaxPlaces2026Seeder;
use Database\Seeders\TaxYear2026Seeder;

/**
 * Double entry bookkeeping on the tax figures.
 *
 * The seeder types the rates out by hand and App\Domain\Tax\TaxYear2026 holds them
 * independently, verified against the primary sources. Neither reads the other, so a digit
 * mistyped in either one makes them disagree here. The reference class earns its keep exactly
 * by no longer being the production source.
 */
beforeEach(function () {
    $this->seed([TaxYear2026Seeder::class, TaxPlaces2026Seeder::class]);
    $this->repository = new TaxYearRepository;
});

it('rebuilds from the database the configuration the engine was verified against', function () {
    $config = $this->repository->configFor(2026, 'Milano');

    expect($config)->toEqual(TaxYear2026::config());
});

it('stores a value for every constant the engine asks for', function () {
    // The engine reads the constants by name, so one missing row is not a smaller
    // configuration, it is a configuration that cannot be built at all.
    // Eloquent applies the cast on the way out, so these come back as enum cases: a row with
    // a key the engine does not know would fail here rather than be quietly ignored.
    $stored = TaxConstant::query()->pluck('key')->all();

    expect($stored)->toHaveCount(count(TaxConstantKey::cases()));

    foreach (TaxConstantKey::cases() as $key) {
        expect($stored)->toContain($key);
    }
});

it('computes the verified net salary from the rates in the database', function () {
    // The end to end check: 35.000 of gross, commerce, 14 payments. The same figure the
    // TypeScript implementation produced, itself verified across 29 salaries.
    $calculator = new SalaryCalculator(
        new ContributionsCalculator,
        new IrpefCalculator,
        new ReliefsCalculator,
        new SurtaxesCalculator,
        new PayslipsCalculator,
    );

    $breakdown = $calculator->calculate(
        new SalaryInput(grossAnnualSalary: 35_000, monthlyPaymentsCount: 14, sector: Sector::Commerce),
        $this->repository->configFor(2026, 'Milano'),
    );

    expect(round($breakdown->netAnnualSalary, 2))->toBe(25_967.22);
});

it('reads the bands in the order they were stored, not the order they came back', function () {
    // A scale sorted by rate instead of by bound computes a plausible and wrong tax, so the
    // ordering is asserted rather than assumed.
    $brackets = $this->repository->configFor(2026, 'Milano')->irpefBrackets;

    expect(array_column($brackets, 'upTo'))->toBe([28_000.0, 50_000.0, null]);
});

it('refuses a year that has not been published', function () {
    TaxYear::query()->where('year', 2026)->update(['published_at' => null]);

    expect(fn () => $this->repository->configFor(2026, 'Milano'))
        ->toThrow(MissingTaxDataException::class);
});

it('refuses a municipality it has no rates for', function () {
    expect(fn () => $this->repository->configFor(2026, 'Cinisello Balsamo'))
        ->toThrow(MissingTaxDataException::class);
});

it('refuses a city whose region has no rates rather than treating the surtax as zero', function () {
    // Roma is seeded with its own verified rate, but Lazio's bands have not been researched.
    // Reading that silence as a surtax of zero would cost the taxpayer a few hundred euro and
    // look entirely ordinary on screen.
    expect(fn () => $this->repository->configFor(2026, 'Roma'))
        ->toThrow(MissingTaxDataException::class);
});

it('refuses to build a configuration when a constant is missing', function () {
    TaxConstant::query()
        ->where('key', TaxConstantKey::SupplementaryAllowanceCapacityTestReliefOffset)
        ->delete();

    expect(fn () => $this->repository->configFor(2026, 'Milano'))
        ->toThrow(MissingTaxDataException::class);
});
