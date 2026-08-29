<?php

namespace App\Domain\Payslips;

/**
 * Everything the schedule needs, already computed for the year.
 *
 * Gathered into one object because seven positional arguments of the same type are trivially
 * swapped by mistake, and nothing would fail loudly if they were.
 *
 * Note what is absent: no tax rates, no brackets, no configuration. Splitting a total across
 * payslips is not a fiscal rule, so this calculator is handed results rather than laws.
 */
final readonly class PayslipsInput
{
    /**
     * @param  float  $marginalRate  Rate the extra payslips are withheld at.
     */
    public function __construct(
        public float $grossAnnualSalary,
        public int $monthlyPaymentsCount,
        public float $netAnnualSalary,
        public float $contributions,
        public float $irpef,
        public float $surtaxes,
        public float $taxFreeAdditions,
        public float $marginalRate,
    ) {}
}
