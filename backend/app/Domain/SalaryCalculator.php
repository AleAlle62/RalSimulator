<?php

namespace App\Domain;

use App\Domain\Payslips\PayslipSchedule;
use App\Domain\Payslips\PayslipsCalculator;
use App\Domain\Payslips\PayslipsInput;
use App\Domain\Tax\Contributions\Contributions;
use App\Domain\Tax\Contributions\ContributionsCalculator;
use App\Domain\Tax\Irpef\IrpefCalculator;
use App\Domain\Tax\Reliefs\Reliefs;
use App\Domain\Tax\Reliefs\ReliefsCalculator;
use App\Domain\Tax\Surtaxes\Surtaxes;
use App\Domain\Tax\Surtaxes\SurtaxesCalculator;
use App\Domain\Tax\TaxYearConfig;

/**
 * Walks a gross annual salary down to the net, in the order payroll applies the rules.
 *
 * The order is the point: contributions come first because they shrink the base everything
 * else is computed on. Getting it wrong does not raise an error, it quietly returns a number
 * that looks plausible.
 *
 * The tax year is passed in rather than looked up, so the engine never learns whether the
 * rates came from a test fixture or from the database.
 */
final readonly class SalaryCalculator
{
    public function __construct(
        private ContributionsCalculator $contributions,
        private IrpefCalculator $irpef,
        private ReliefsCalculator $reliefs,
        private SurtaxesCalculator $surtaxes,
        private PayslipsCalculator $payslips,
    ) {}

    public function calculate(SalaryInput $input, TaxYearConfig $config): SalaryBreakdown
    {
        $gross = $input->grossAnnualSalary;

        $contributions = $this->contributions->calculate($gross, $input->sector, $config->contributions);
        $taxableIncome = $gross - $contributions->total;

        $grossIrpef = $this->irpef->grossTax($taxableIncome, $config->irpefBrackets);
        $reliefs = $this->reliefs->calculate($taxableIncome, $grossIrpef, $config->reliefs);
        $netIrpef = $this->netIrpef($grossIrpef, $reliefs);

        $surtaxes = $this->surtaxes->calculate($taxableIncome, $config->surtaxes);

        $withheld = $contributions->total + $netIrpef + $surtaxes->total;
        $added = $reliefs->taxFreeAdditions();
        $net = $gross - $withheld + $added;

        return new SalaryBreakdown(
            input: $input,
            year: $config->year,
            grossAnnualSalary: $gross,
            contributions: $contributions,
            taxableIncome: $taxableIncome,
            grossIrpef: $grossIrpef,
            reliefs: $reliefs,
            netIrpef: $netIrpef,
            surtaxes: $surtaxes,
            totalWithholdings: $withheld,
            taxFreeAdditions: $added,
            netAnnualSalary: $net,
            payslips: $this->payslipsFor($input, $config, $contributions, $netIrpef, $surtaxes, $added, $net),
        );
    }

    /** Reliefs beyond the tax are lost: the excess never turns into a credit. */
    private function netIrpef(float $grossIrpef, Reliefs $reliefs): float
    {
        return max(0, $grossIrpef - $reliefs->totalDeductedFromTax());
    }

    /**
     * Splitting the year into payslips needs results, not rules, so everything handed over
     * here is already computed. The marginal rate is the one exception, and only because the
     * extra payslips are withheld at it.
     */
    private function payslipsFor(
        SalaryInput $input,
        TaxYearConfig $config,
        Contributions $contributions,
        float $netIrpef,
        Surtaxes $surtaxes,
        float $taxFreeAdditions,
        float $netAnnualSalary,
    ): PayslipSchedule {
        $taxableIncome = $input->grossAnnualSalary - $contributions->total;

        return $this->payslips->calculate(new PayslipsInput(
            grossAnnualSalary: $input->grossAnnualSalary,
            monthlyPaymentsCount: $input->monthlyPaymentsCount,
            netAnnualSalary: $netAnnualSalary,
            contributions: $contributions->total,
            irpef: $netIrpef,
            surtaxes: $surtaxes->total,
            taxFreeAdditions: $taxFreeAdditions,
            marginalRate: $this->irpef->marginalRate($taxableIncome, $config->irpefBrackets),
        ));
    }
}
