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

/**
 * Assembled by hand rather than resolved from the container: if this ever needs Laravel to
 * run, the domain has quietly picked up a dependency on the framework.
 */
function salaryCalculator(): SalaryCalculator
{
    return new SalaryCalculator(
        new ContributionsCalculator,
        new IrpefCalculator,
        new ReliefsCalculator,
        new SurtaxesCalculator,
        new PayslipsCalculator,
    );
}

function breakdownFor(float $gross, int $payments = 14, Sector $sector = Sector::Commerce)
{
    return salaryCalculator()->calculate(
        new SalaryInput($gross, $payments, $sector),
        TaxYear2026::config(),
    );
}

/**
 * Reference case, computed by hand from the 2026 parameters:
 *
 *   contributions   35.000 x 9,19%                                     =  3.216,50
 *   taxable income  35.000 - 3.216,50                                  = 31.783,50
 *   gross irpef     28.000 x 23% + 3.783,50 x 33%                      =  7.688,56
 *   employment      1.910 x (50.000 - 31.783,50) / 22.000              =  1.581,52
 *   wedge cut       flat, the income sits below the 32.000 taper       =  1.000,00
 *   net irpef       7.688,56 - 1.581,52 - 1.000                        =  5.107,03
 *   regional        15.000 x 1,23% + 13.000 x 1,58% + 3.783,50 x 1,72% =    454,98
 *   municipal       31.783,50 x 0,8%, threshold passed                 =    254,27
 *   net salary      35.000 - 3.216,50 - 5.107,03 - 454,98 - 254,27     = 25.967,22
 */
describe('caso di riferimento: 35.000 lordi, commercio, 14 mensilità', function () {
    it('withholds contributions before working out the taxable income', function () {
        $result = breakdownFor(35_000);

        expect($result->contributions->total)->toEqualWithDelta(3_216.50, 0.01)
            ->and($result->taxableIncome)->toEqualWithDelta(31_783.50, 0.01);
    });

    it('works out gross irpef, reliefs and net irpef', function () {
        $result = breakdownFor(35_000);

        expect($result->grossIrpef)->toEqualWithDelta(7_688.56, 0.01)
            ->and($result->reliefs->employmentRelief)->toEqualWithDelta(1_581.52, 0.01)
            ->and($result->reliefs->wedgeCutRelief)->toEqualWithDelta(1_000, 0.01)
            ->and($result->netIrpef)->toEqualWithDelta(5_107.03, 0.01);
    });

    it('works out both local surtaxes', function () {
        $result = breakdownFor(35_000);

        expect($result->surtaxes->regional)->toEqualWithDelta(454.98, 0.01)
            ->and($result->surtaxes->municipal)->toEqualWithDelta(254.27, 0.01);
    });

    it('reaches the expected annual net salary', function () {
        expect(breakdownFor(35_000)->netAnnualSalary)->toEqualWithDelta(25_967.22, 0.01);
    });

    it('reaches the expected payslips', function () {
        $payslips = breakdownFor(35_000)->payslips;

        expect($payslips->ordinary->net)->toEqualWithDelta(1_910.42, 0.01)
            ->and($payslips->extras[0]->net)->toEqualWithDelta(1_521.07, 0.01);
    });
});

describe('coerenza su tutto l’arco dei redditi', function () {
    $salaries = [12_000, 18_000, 25_000, 35_000, 55_000, 80_000, 130_000];

    it('always leaves less than the gross, and more than nothing', function () use ($salaries) {
        foreach ($salaries as $salary) {
            $net = breakdownFor($salary)->netAnnualSalary;

            expect($net)->toBeLessThan($salary)->toBeGreaterThan(0);
        }
    });

    it('balances gross against withholdings, exempt sums and net', function () use ($salaries) {
        foreach ($salaries as $salary) {
            $result = breakdownFor($salary);

            expect($result->netAnnualSalary + $result->totalWithholdings - $result->taxFreeAdditions)
                ->toEqualWithDelta($salary, 0.01);
        }
    });

    it('leaves an industry employee with less than a commerce one', function () {
        expect(breakdownFor(35_000, 14, Sector::Industry)->netAnnualSalary)
            ->toBeLessThan(breakdownFor(35_000, 14, Sector::Commerce)->netAnnualSalary);
    });

    it('pays the same for the year whatever the number of payslips', function () {
        $twelve = breakdownFor(35_000, 12)->netAnnualSalary;

        expect(breakdownFor(35_000, 13)->netAnnualSalary)->toEqualWithDelta($twelve, 0.01)
            ->and(breakdownFor(35_000, 14)->netAnnualSalary)->toEqualWithDelta($twelve, 0.01);
    });
});

/**
 * Three thresholds are cliffs rather than tapers: cross one by a euro and a benefit is
 * recomputed on the whole income, or lost outright. The net salary genuinely falls, and this
 * is the test that proves the falls are the law rather than a bug in the engine.
 */
describe('i tre gradini', function () {
    it('drops the net just past the Milano exemption', function () {
        $below = breakdownFor(25_320);
        $above = breakdownFor(25_330);

        expect($below->taxableIncome)->toBeLessThan(23_000)
            ->and($above->taxableIncome)->toBeGreaterThan(23_000)
            ->and($above->netAnnualSalary)->toBeLessThan($below->netAnnualSalary);
    });

    it('drops the net when the supplementary allowance is lost', function () {
        $below = breakdownFor(16_500);
        $above = breakdownFor(16_600);

        expect($below->reliefs->supplementaryAllowance)->toBe(1_200.0)
            ->and($above->reliefs->supplementaryAllowance)->toBe(0.0)
            ->and($above->netAnnualSalary)->toBeLessThan($below->netAnnualSalary);
    });

    it('only lowers the net where one of the three thresholds is crossed', function () {
        $cliffs = [
            8_500,  // the exempt wedge cut rate drops from 7,1% to 5,3% of the whole income
            15_000, // the supplementary allowance is lost, the exempt rate drops to 4,8%
            23_000, // the Milano exemption ends
        ];

        $previous = breakdownFor(1_000);

        for ($salary = 1_100; $salary <= 300_000; $salary += 100) {
            $current = breakdownFor($salary);

            if ($current->netAnnualSalary < $previous->netAnnualSalary) {
                $crossed = array_filter(
                    $cliffs,
                    fn ($cliff) => $previous->taxableIncome <= $cliff && $current->taxableIncome > $cliff,
                );

                expect($crossed)->not->toBeEmpty("calo del netto non spiegato a {$salary} € di lordo");
            }

            $previous = $current;
        }
    });

    it('climbs back out of every cliff within a few hundred euro', function () {
        // A cliff is acceptable because it is the law; one the salary never recovers from
        // would be a modelling error.
        foreach ([9_400, 16_600, 25_400] as $salary) {
            expect(breakdownFor($salary + 3_000)->netAnnualSalary)
                ->toBeGreaterThan(breakdownFor($salary)->netAnnualSalary);
        }
    });
});
