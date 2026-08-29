<?php

use App\Domain\Tax\Contributions\ContributionsCalculator;
use App\Domain\Tax\Contributions\ContributionsConfig;
use App\Domain\Tax\Sector;

/** The 2026 parameters. Source: INPS, Circolare n. 6 del 30/01/2026. */
function contributionsConfig2026(): ContributionsConfig
{
    return new ContributionsConfig(
        employeeRateBySector: [
            Sector::Commerce->value => 0.0919,
            Sector::Industry->value => 0.0949,
        ],
        additionalRateThreshold: 56_224,
        additionalRate: 0.01,
        annualCeiling: 122_295,
    );
}

function contributionsOn(float $grossAnnualSalary, Sector $sector = Sector::Commerce)
{
    return (new ContributionsCalculator)
        ->calculate($grossAnnualSalary, $sector, contributionsConfig2026());
}

describe('l’aliquota di settore', function () {
    it('charges the commerce rate on a salary below every threshold', function () {
        $result = contributionsOn(35_000);

        expect($result->total)->toEqualWithDelta(3_216.5, 0.01)
            ->and($result->baseRate)->toBe(0.0919);
    });

    it('charges industry the extra CIGS point over commerce', function () {
        $difference = contributionsOn(35_000, Sector::Industry)->total
            - contributionsOn(35_000, Sector::Commerce)->total;

        expect($difference)->toEqualWithDelta(35_000 * 0.003, 0.01);
    });
});

describe('il punto in più oltre i 56.224', function () {
    it('charges nothing extra below the threshold', function () {
        expect(contributionsOn(56_224)->additionalRateAmount)->toBe(0.0);
    });

    it('charges the extra point only on the slice above the threshold', function () {
        // 66.224 - 56.224 = 10.000, and one per cent of that is 100.
        expect(contributionsOn(66_224)->additionalRateAmount)->toEqualWithDelta(100, 0.01);
    });

    it('adds the extra point on top of the base amount', function () {
        expect(contributionsOn(66_224)->total)
            ->toEqualWithDelta(66_224 * 0.0919 + 100, 0.01);
    });
});

describe('il massimale annuo', function () {
    it('stops raising the contributory base above the ceiling', function () {
        expect(contributionsOn(200_000)->contributoryBase)->toBe(122_295.0);
    });

    it('charges a very high salary exactly what it charges at the ceiling', function () {
        expect(contributionsOn(200_000)->total)
            ->toEqualWithDelta(contributionsOn(122_295)->total, 0.01);
    });
});
