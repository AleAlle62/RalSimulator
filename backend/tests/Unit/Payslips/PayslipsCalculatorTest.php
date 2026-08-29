<?php

use App\Domain\Payslips\PayslipKind;
use App\Domain\Payslips\PayslipsCalculator;
use App\Domain\Payslips\PayslipsInput;

/**
 * The reference case: 35.000 gross, commerce, already run through the tax engine by hand.
 * Only the number of payments changes between calls.
 */
function scheduleFor(int $monthlyPaymentsCount)
{
    return (new PayslipsCalculator)->calculate(new PayslipsInput(
        grossAnnualSalary: 35_000,
        monthlyPaymentsCount: $monthlyPaymentsCount,
        netAnnualSalary: 25_967.22,
        contributions: 3_216.50,
        irpef: 5_107.03,
        surtaxes: 709.25,
        taxFreeAdditions: 0,
        marginalRate: 0.33,
    ));
}

/** A low salary, where reliefs wipe out the whole annual tax and exempt sums arrive instead. */
function lowSalarySchedule()
{
    return (new PayslipsCalculator)->calculate(new PayslipsInput(
        grossAnnualSalary: 16_000,
        monthlyPaymentsCount: 14,
        netAnnualSalary: 15_236.66,
        contributions: 1_470.40,
        irpef: 0,
        surtaxes: 178.51,
        taxFreeAdditions: 885.57,
        marginalRate: 0.23,
    ));
}

describe('quante buste', function () {
    it('produces no extras on a twelve month contract', function () {
        expect(scheduleFor(12)->extras)->toBeEmpty();
    });

    it('produces a thirteenth, then a fourteenth', function () {
        expect(scheduleFor(13)->extras)->toHaveCount(1)
            ->and(scheduleFor(13)->extras[0]->kind)->toBe(PayslipKind::Thirteenth)
            ->and(scheduleFor(14)->extras)->toHaveCount(2)
            ->and(scheduleFor(14)->extras[1]->kind)->toBe(PayslipKind::Fourteenth);
    });
});

describe('i conti tornano', function () {
    it('splits the annual net across every payslip without losing a cent', function () {
        foreach ([12, 13, 14] as $count) {
            $schedule = scheduleFor($count);
            $extrasNet = array_sum(array_map(fn ($p) => $p->net, $schedule->extras));

            expect($schedule->ordinary->net * 12 + $extrasNet)
                ->toEqualWithDelta(25_967.22, 0.01);
        }
    });

    it('reconciles every payslip with its own lines', function () {
        foreach ([scheduleFor(14), lowSalarySchedule()] as $schedule) {
            foreach ([$schedule->ordinary, ...$schedule->extras] as $payslip) {
                $fromLines = $payslip->gross
                    - $payslip->contributions
                    - $payslip->irpef
                    - $payslip->surtaxes
                    + $payslip->taxFreeAdditions;

                expect($fromLines)->toEqualWithDelta($payslip->net, 0.01);
            }
        }
    });

    it('divides the gross evenly across the payslips', function () {
        $schedule = scheduleFor(14);

        expect($schedule->ordinary->gross)->toEqualWithDelta(35_000 / 14, 0.01)
            ->and($schedule->extras[0]->gross)->toEqualWithDelta($schedule->ordinary->gross, 0.01);
    });
});

describe('le extra rendono meno', function () {
    it('pays an extra month less than an ordinary one at the same gross', function () {
        $schedule = scheduleFor(14);

        foreach ($schedule->extras as $extra) {
            expect($extra->gross)->toEqualWithDelta($schedule->ordinary->gross, 0.01)
                ->and($extra->net)->toBeLessThan($schedule->ordinary->net);
        }
    });

    it('withholds no local surtax on the extras', function () {
        foreach (scheduleFor(14)->extras as $extra) {
            expect($extra->surtaxes)->toBe(0.0);
        }
    });

    it('withholds more tax on an extra than on an ordinary month', function () {
        $schedule = scheduleFor(14);

        expect($schedule->extras[0]->irpef)->toBeGreaterThan($schedule->ordinary->irpef);
    });
});

describe('il limite sull’imposta delle extra', function () {
    /**
     * Reliefs can wipe out the whole annual liability. Without the cap, payroll would withhold
     * the marginal rate on the extras and hand back a negative tax on the ordinary ones.
     */
    it('never withholds more than the tax owed for the year', function () {
        $schedule = lowSalarySchedule();
        $extrasIrpef = array_sum(array_map(fn ($p) => $p->irpef, $schedule->extras));

        expect($extrasIrpef)->toBeLessThanOrEqual(0.01)
            ->and($schedule->ordinary->irpef)->toBeGreaterThanOrEqual(0);
    });

    it('still balances the year when there is no tax at all', function () {
        $schedule = lowSalarySchedule();
        $extrasNet = array_sum(array_map(fn ($p) => $p->net, $schedule->extras));

        expect($schedule->ordinary->net * 12 + $extrasNet)
            ->toEqualWithDelta(15_236.66, 0.01);
    });
});
