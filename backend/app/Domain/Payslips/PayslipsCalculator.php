<?php

namespace App\Domain\Payslips;

/**
 * Spreads the annual figures across actual payslips.
 *
 * Extra payments are worth less in the hand than an ordinary month at the same gross, because
 * the reliefs are spent on the twelve ordinary payslips and the local surtaxes are withheld
 * there too. Showing an averaged monthly figure would hide the difference employees see.
 *
 * The annual totals are the truth and the parts are derived from them, never the other way
 * round, so the payslips always add back up to the annual net to the cent.
 */
final class PayslipsCalculator
{
    private const ORDINARY_PER_YEAR = 12;

    /** @var array<int, PayslipKind> */
    private const EXTRA_KINDS = [PayslipKind::Thirteenth, PayslipKind::Fourteenth];

    public function calculate(PayslipsInput $input): PayslipSchedule
    {
        $gross = $input->grossAnnualSalary / $input->monthlyPaymentsCount;
        $contributions = $gross * ($input->contributions / $input->grossAnnualSalary);

        $extraCount = $input->monthlyPaymentsCount - self::ORDINARY_PER_YEAR;
        $extraIrpef = $this->extraIrpef($gross - $contributions, $input, $extraCount);

        $extras = $this->extras($extraCount, $gross, $contributions, $extraIrpef);

        return new PayslipSchedule(
            ordinary: $this->ordinary($gross, $contributions, $input, $extraIrpef, $extras),
            extras: $extras,
        );
    }

    /**
     * Tax withheld across all the extra payslips, at the marginal rate.
     *
     * Capped at the tax actually owed for the year: on low salaries the reliefs wipe out the
     * whole liability, and without the cap payroll would withhold tax that does not exist.
     */
    private function extraIrpef(float $taxablePerPayslip, PayslipsInput $input, int $extraCount): float
    {
        $uncapped = $taxablePerPayslip * $input->marginalRate * $extraCount;

        return min($uncapped, $input->irpef);
    }

    /**
     * @return array<int, Payslip>
     */
    private function extras(int $count, float $gross, float $contributions, float $totalIrpef): array
    {
        $irpef = $count === 0 ? 0.0 : $totalIrpef / $count;

        return array_map(
            fn (PayslipKind $kind) => new Payslip(
                kind: $kind,
                gross: $gross,
                contributions: $contributions,
                irpef: $irpef,
                // Neither local surtaxes nor exempt sums ride on the extra payslips.
                surtaxes: 0.0,
                taxFreeAdditions: 0.0,
                net: $gross - $contributions - $irpef,
            ),
            array_slice(self::EXTRA_KINDS, 0, $count),
        );
    }

    /**
     * @param  array<int, Payslip>  $extras
     */
    private function ordinary(
        float $gross,
        float $contributions,
        PayslipsInput $input,
        float $extraIrpef,
        array $extras,
    ): Payslip {
        $netLeftForOrdinaries = $input->netAnnualSalary - $this->netOf($extras);

        return new Payslip(
            kind: PayslipKind::Ordinary,
            gross: $gross,
            contributions: $contributions,
            irpef: ($input->irpef - $extraIrpef) / self::ORDINARY_PER_YEAR,
            surtaxes: $input->surtaxes / self::ORDINARY_PER_YEAR,
            taxFreeAdditions: $input->taxFreeAdditions / self::ORDINARY_PER_YEAR,
            net: $netLeftForOrdinaries / self::ORDINARY_PER_YEAR,
        );
    }

    /**
     * @param  array<int, Payslip>  $payslips
     */
    private function netOf(array $payslips): float
    {
        return array_sum(array_map(fn (Payslip $payslip) => $payslip->net, $payslips));
    }
}
