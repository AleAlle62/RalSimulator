<?php

use App\Domain\Tax\Brackets\Bracket;
use App\Domain\Tax\Surtaxes\SurtaxesCalculator;
use App\Domain\Tax\Surtaxes\SurtaxesConfig;

/**
 * The 2026 parameters. Sources: Regione Lombardia, addizionale regionale IRPEF; Comune di
 * Milano, whose 23.000 threshold has been in force since 2020.
 */
function surtaxesConfig2026(): SurtaxesConfig
{
    return new SurtaxesConfig(
        region: 'Lombardia',
        regionalBrackets: [
            new Bracket(upTo: 15_000, rate: 0.0123),
            new Bracket(upTo: 28_000, rate: 0.0158),
            new Bracket(upTo: 50_000, rate: 0.0172),
            new Bracket(upTo: null, rate: 0.0173),
        ],
        municipality: 'Milano',
        municipalRate: 0.008,
        municipalExemptionThreshold: 23_000,
    );
}

function surtaxesOn(float $taxableIncome)
{
    return (new SurtaxesCalculator)->calculate($taxableIncome, surtaxesConfig2026());
}

describe('addizionale regionale', function () {
    it('slices the income across the Lombardia bands', function () {
        $expected = 15_000 * 0.0123 + 13_000 * 0.0158 + 3_783.5 * 0.0172;

        expect(surtaxesOn(31_783.5)->regional)->toEqualWithDelta($expected, 0.01);
    });

    it('charges the lowest band alone on a small income', function () {
        expect(surtaxesOn(12_000)->regional)->toEqualWithDelta(12_000 * 0.0123, 0.01);
    });
});

describe('addizionale comunale di Milano', function () {
    it('charges nothing up to the threshold', function () {
        expect(surtaxesOn(23_000)->municipal)->toBe(0.0);
    });

    /**
     * The threshold is an exemption, not an allowance. Reading it as an allowance would
     * charge 0,8% of one euro here instead of 0,8% of the whole income: the difference is
     * roughly 184 euro, and it is the mistake that hides the step in the net salary curve.
     */
    it('charges the whole income once the threshold is passed', function () {
        expect(surtaxesOn(23_001)->municipal)->toEqualWithDelta(23_001 * 0.008, 0.01);
    });

    it('drops the take home by crossing the threshold', function () {
        $below = surtaxesOn(23_000)->total;
        $above = surtaxesOn(23_001)->total;

        expect($above - $below)->toBeGreaterThan(180);
    });
});

describe('il totale', function () {
    it('adds both surtaxes together', function () {
        $result = surtaxesOn(31_783.5);

        expect($result->total)->toEqualWithDelta($result->regional + $result->municipal, 0.01)
            ->and($result->total)->toEqualWithDelta(709.25, 0.01);
    });

    it('reports only the regional one below the municipal threshold', function () {
        $result = surtaxesOn(20_000);

        expect($result->municipal)->toBe(0.0)
            ->and($result->total)->toEqualWithDelta($result->regional, 0.01);
    });
});
