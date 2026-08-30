<?php

namespace App\Domain;

use App\Domain\Payslips\PayslipSchedule;
use App\Domain\Tax\Contributions\Contributions;
use App\Domain\Tax\Reliefs\Reliefs;
use App\Domain\Tax\Surtaxes\Surtaxes;

/**
 * The whole journey from gross to net, kept in domain terms.
 *
 * Every screen reads a different slice of this: the headline number takes the net, the chart
 * takes the three withholdings, the line by line view takes all of it. Turning it into JSON
 * is somebody else's job, in the Http layer, so the shape of the API can change without
 * touching the calculation.
 *
 * `taxFreeAdditions` is the one to be careful with: those sums do not come out of the gross,
 * they are added on top of it. A chart that treated them as a slice of the RAL would show
 * shares adding up to more than the whole.
 */
final readonly class SalaryBreakdown
{
    public function __construct(
        public SalaryInput $input,
        public int $year,
        public float $grossAnnualSalary,
        public Contributions $contributions,
        public float $taxableIncome,
        public float $grossIrpef,
        public Reliefs $reliefs,
        public float $netIrpef,
        public Surtaxes $surtaxes,
        public float $totalWithholdings,
        public float $taxFreeAdditions,
        public float $netAnnualSalary,
        public PayslipSchedule $payslips,
    ) {}

    /** Share of the gross salary withheld as contributions and taxes. */
    public function effectiveWithholdingRate(): float
    {
        return $this->totalWithholdings / $this->grossAnnualSalary;
    }
}
