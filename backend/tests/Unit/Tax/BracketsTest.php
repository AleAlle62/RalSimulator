<?php

use App\Domain\Tax\Brackets\Bracket;
use App\Domain\Tax\Brackets\Brackets;

/** The 2026 income tax bands, used as the fixture for the sliced calculation. */
function incomeTaxBrackets(): array
{
    return [
        new Bracket(upTo: 28_000, rate: 0.23),
        new Bracket(upTo: 50_000, rate: 0.33),
        new Bracket(upTo: null, rate: 0.43),
    ];
}

describe('apply: divide il reddito a fette', function () {
    it('charges each band its own rate on its own slice', function () {
        $expected = 28_000 * 0.23 + 22_000 * 0.33 + 10_000 * 0.43;

        expect(Brackets::apply(60_000, incomeTaxBrackets()))->toEqualWithDelta($expected, 0.01);
    });

    it('stops at the income instead of filling the whole band', function () {
        // 20.000 sits inside the first band: nothing is owed at 33% or 43%.
        expect(Brackets::apply(20_000, incomeTaxBrackets()))->toEqualWithDelta(20_000 * 0.23, 0.01);
    });

    it('taxes an income that lands exactly on a boundary at the lower rate', function () {
        expect(Brackets::apply(28_000, incomeTaxBrackets()))->toEqualWithDelta(28_000 * 0.23, 0.01);
    });

    it('owes nothing on no income', function () {
        expect(Brackets::apply(0, incomeTaxBrackets()))->toBe(0.0);
    });

    /**
     * The property the whole simulator leans on: a raise can never leave you with less. Any
     * drop in the net salary must come from a rule outside the brackets, never from here.
     */
    it('never lets one more euro of income lower the take home', function () {
        for ($income = 0; $income <= 120_000; $income += 500) {
            $tax = Brackets::apply($income, incomeTaxBrackets());
            $taxOnOneMore = Brackets::apply($income + 1, incomeTaxBrackets());

            expect($taxOnOneMore - $tax)->toBeLessThanOrEqual(1.0);
        }
    });
});

describe('rateFor: sceglie una fascia sola', function () {
    /** The wedge cut bonus picks a rate by band, then applies it to the whole income. */
    $wedgeCutBands = [
        new Bracket(upTo: 8_500, rate: 0.071),
        new Bracket(upTo: 15_000, rate: 0.053),
        new Bracket(upTo: 20_000, rate: 0.048),
    ];

    it('returns the rate of the band the income falls into', function () use ($wedgeCutBands) {
        expect(Brackets::rateFor(18_000, $wedgeCutBands))->toBe(0.048);
    });

    it('reads a boundary as belonging to the lower band', function () use ($wedgeCutBands) {
        expect(Brackets::rateFor(8_500, $wedgeCutBands))->toBe(0.071)
            ->and(Brackets::rateFor(8_501, $wedgeCutBands))->toBe(0.053);
    });

    it('returns nothing when the income is past every band', function () use ($wedgeCutBands) {
        expect(Brackets::rateFor(25_000, $wedgeCutBands))->toBe(0.0);
    });

    it('reaches the unbounded band when there is one', function () {
        expect(Brackets::rateFor(500_000, incomeTaxBrackets()))->toBe(0.43);
    });
});
