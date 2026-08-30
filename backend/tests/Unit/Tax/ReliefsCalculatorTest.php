<?php

use App\Domain\Tax\Brackets\Bracket;
use App\Domain\Tax\Reliefs\EmploymentReliefConfig;
use App\Domain\Tax\Reliefs\ReliefsCalculator;
use App\Domain\Tax\Reliefs\ReliefsConfig;
use App\Domain\Tax\Reliefs\SupplementaryAllowanceConfig;
use App\Domain\Tax\Reliefs\WedgeCutConfig;

/**
 * The 2026 parameters. Sources: art. 13 TUIR for the employment relief, the taglio del cuneo
 * fiscale, and art. 1 D.L. 3/2020 as amended by L. 207/2024 for the allowance.
 */
function reliefsConfig2026(): ReliefsConfig
{
    return new ReliefsConfig(
        employment: new EmploymentReliefConfig(
            flatAmountUpTo: 15_000,
            flatAmount: 1_955,
            firstTaperUpTo: 28_000,
            firstTaperBase: 1_910,
            firstTaperVariable: 1_190,
            secondTaperUpTo: 50_000,
            secondTaperBase: 1_910,
        ),
        wedgeCut: new WedgeCutConfig(
            exemptBonusUpTo: 20_000,
            exemptBonusBrackets: [
                new Bracket(upTo: 8_500, rate: 0.071),
                new Bracket(upTo: 15_000, rate: 0.053),
                new Bracket(upTo: 20_000, rate: 0.048),
            ],
            reliefFlatUpTo: 32_000,
            reliefFlatAmount: 1_000,
            reliefTaperUpTo: 40_000,
        ),
        supplementaryAllowance: new SupplementaryAllowanceConfig(
            fullAmountUpTo: 15_000,
            fullAmount: 1_200,
            partialUpTo: 28_000,
            capacityTestReliefOffset: 75,
        ),
    );
}

function employmentReliefOn(float $taxableIncome): float
{
    return (new ReliefsCalculator)
        ->employmentRelief($taxableIncome, reliefsConfig2026()->employment);
}

function wedgeCutOn(float $taxableIncome): array
{
    $calculator = new ReliefsCalculator;
    $config = reliefsConfig2026()->wedgeCut;

    return [
        'bonus' => $calculator->wedgeCutExemptBonus($taxableIncome, $config),
        'relief' => $calculator->wedgeCutRelief($taxableIncome, $config),
    ];
}

function allowanceOn(float $taxableIncome, float $grossTax): float
{
    $config = reliefsConfig2026();

    return (new ReliefsCalculator)->supplementaryAllowance(
        $taxableIncome,
        $grossTax,
        (new ReliefsCalculator)->employmentRelief($taxableIncome, $config->employment),
        $config->supplementaryAllowance,
    );
}

describe('detrazione per lavoro dipendente', function () {
    it('grants the flat amount on low incomes', function () {
        expect(employmentReliefOn(12_000))->toBe(1_955.0)
            ->and(employmentReliefOn(15_000))->toBe(1_955.0);
    });

    it('keeps the base and shrinks only the variable share on the first taper', function () {
        // At the very end of the first taper only the base is left.
        expect(employmentReliefOn(28_000))->toEqualWithDelta(1_910, 0.01);
    });

    it('shrinks the base itself on the second taper', function () {
        // 1.910 x (50.000 - 31.783,50) / 22.000
        expect(employmentReliefOn(31_783.5))->toEqualWithDelta(1_581.52, 0.01);
    });

    it('grants nothing above the second threshold', function () {
        expect(employmentReliefOn(50_000))->toEqualWithDelta(0, 0.01)
            ->and(employmentReliefOn(51_000))->toBe(0.0);
    });

    /**
     * The relief is not monotonic, and the jump is in the statute rather than in the code:
     * the first taper starts at 1.910 + 1.190 = 3.100, well above the 1.955 flat amount just
     * below it. Crossing 15.000 therefore buys more relief, not less.
     *
     * The net salary still falls at that threshold, but for other reasons: the supplementary
     * allowance is lost and the exempt wedge cut rate drops. Those are tested where they live.
     */
    it('jumps up when the flat amount gives way to the first taper', function () {
        expect(employmentReliefOn(15_000))->toBe(1_955.0)
            ->and(employmentReliefOn(15_001))->toBeGreaterThan(3_050);
    });

    it('never rises within a band as the income rises', function () {
        foreach ([[15_001, 28_000], [28_001, 50_000]] as [$from, $to]) {
            $previous = employmentReliefOn($from);

            for ($income = $from + 100; $income <= $to; $income += 100) {
                $current = employmentReliefOn($income);

                expect($current)->toBeLessThanOrEqual($previous + 0.01);
                $previous = $current;
            }
        }
    });
});

describe('taglio del cuneo', function () {
    it('pays an exempt sum below the threshold, and no relief', function () {
        $result = wedgeCutOn(18_000);

        expect($result['bonus'])->toEqualWithDelta(18_000 * 0.048, 0.01)
            ->and($result['relief'])->toBe(0.0);
    });

    it('picks the rate by band and applies it to the whole income', function () {
        expect(wedgeCutOn(8_000)['bonus'])->toEqualWithDelta(8_000 * 0.071, 0.01)
            ->and(wedgeCutOn(12_000)['bonus'])->toEqualWithDelta(12_000 * 0.053, 0.01);
    });

    it('grants a relief above the threshold, and no exempt sum', function () {
        $result = wedgeCutOn(25_000);

        expect($result['relief'])->toBe(1_000.0)
            ->and($result['bonus'])->toBe(0.0);
    });

    it('tapers the relief to zero between 32.000 and 40.000', function () {
        expect(wedgeCutOn(36_000)['relief'])->toEqualWithDelta(500, 0.01)
            ->and(wedgeCutOn(40_000)['relief'])->toBe(0.0)
            ->and(wedgeCutOn(45_000)['relief'])->toBe(0.0);
    });

    it('never grants both branches at once', function () {
        for ($income = 1_000; $income <= 50_000; $income += 500) {
            $result = wedgeCutOn($income);

            expect($result['bonus'] * $result['relief'])->toBe(0.0);
        }
    });
});

describe('trattamento integrativo', function () {
    it('grants it in full when the tax clears the capacity test', function () {
        // 14.000 x 23% = 3.220 of gross tax, well above the 1.880 bar.
        expect(allowanceOn(14_000, 3_220))->toBe(1_200.0);
    });

    it('denies it when the relief already absorbs the tax', function () {
        // 8.000 x 23% = 1.840, below the bar: nothing to top up.
        expect(allowanceOn(8_000, 1_840))->toBe(0.0);
    });

    /**
     * The band the offset exists for. Gross tax of 1.909 clears 1.955 - 75 but not 1.955:
     * dropping the offset would deny 1.200 euro to someone the law means to protect. This is
     * the single case that separates a correct implementation from most online calculators.
     */
    it('tests the capacity against the relief lowered by 75, not the relief itself', function () {
        expect(allowanceOn(8_300, 1_909))->toBe(1_200.0);
    });

    it('sits exactly on the bar without granting it', function () {
        // 1.880 is not greater than 1.880: the test is strict.
        expect(allowanceOn(8_300, 1_880))->toBe(0.0)
            ->and(allowanceOn(8_300, 1_880.01))->toBe(1_200.0);
    });

    it('grants nothing between 15.000 and 28.000 in this model', function () {
        // Art. 1 co. 1-bis lists arts. 12, 13 and 15 TUIR only. With no dependants and no
        // mortgage interest, the employment relief never outruns the tax in this band.
        foreach ([16_000, 21_000, 25_000, 27_900] as $income) {
            $grossTax = $income * 0.23;

            expect(allowanceOn($income, $grossTax))->toBe(0.0);
        }
    });

    it('grants nothing above the upper threshold', function () {
        expect(allowanceOn(30_000, 7_000))->toBe(0.0);
    });
});

describe('calculate: le quattro insieme', function () {
    it('splits the benefits by where the money lands', function () {
        $reliefs = (new ReliefsCalculator)->calculate(31_783.5, 7_688.56, reliefsConfig2026());

        expect($reliefs->totalDeductedFromTax())->toEqualWithDelta(1_581.52 + 1_000, 0.01)
            ->and($reliefs->taxFreeAdditions())->toBe(0.0);
    });

    it('pays a low income mostly through exempt sums', function () {
        $reliefs = (new ReliefsCalculator)->calculate(14_000, 3_220, reliefsConfig2026());

        expect($reliefs->taxFreeAdditions())->toEqualWithDelta(14_000 * 0.053 + 1_200, 0.01)
            ->and($reliefs->wedgeCutRelief)->toBe(0.0);
    });
});
