<?php

use App\Domain\Tax\Brackets\Bracket;
use App\Domain\Tax\Irpef\IrpefCalculator;

/** Source: L. 199/2025 art. 1 co. 3, which cut the second band from 35% to 33% for 2026. */
function irpefBrackets2026(): array
{
    return [
        new Bracket(upTo: 28_000, rate: 0.23),
        new Bracket(upTo: 50_000, rate: 0.33),
        new Bracket(upTo: null, rate: 0.43),
    ];
}

describe('IRPEF lorda', function () {
    it('charges each band its own rate', function () {
        $expected = 28_000 * 0.23 + 22_000 * 0.33 + 10_000 * 0.43;

        expect((new IrpefCalculator)->grossTax(60_000, irpefBrackets2026()))
            ->toEqualWithDelta($expected, 0.01);
    });

    it('charges the reference case what the hand calculation says', function () {
        // 28.000 x 23% + 3.783,50 x 33%
        expect((new IrpefCalculator)->grossTax(31_783.5, irpefBrackets2026()))
            ->toEqualWithDelta(7_688.56, 0.01);
    });

    it('charges nothing on no income', function () {
        expect((new IrpefCalculator)->grossTax(0, irpefBrackets2026()))->toBe(0.0);
    });
});

describe('aliquota marginale', function () {
    it('reports the rate of the highest band the income reaches', function () {
        $calculator = new IrpefCalculator;

        expect($calculator->marginalRate(20_000, irpefBrackets2026()))->toBe(0.23)
            ->and($calculator->marginalRate(31_783.5, irpefBrackets2026()))->toBe(0.33)
            ->and($calculator->marginalRate(80_000, irpefBrackets2026()))->toBe(0.43);
    });

    it('reads a boundary as belonging to the lower band', function () {
        $calculator = new IrpefCalculator;

        expect($calculator->marginalRate(28_000, irpefBrackets2026()))->toBe(0.23)
            ->and($calculator->marginalRate(28_001, irpefBrackets2026()))->toBe(0.33);
    });
});
