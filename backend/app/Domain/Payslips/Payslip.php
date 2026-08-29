<?php

namespace App\Domain\Payslips;

/**
 * One payslip, kept line by line so it reconciles on its own:
 *
 *     gross − contributions − irpef − surtaxes + taxFreeAdditions = net
 *
 * The exempt sums are what makes that add up on low salaries. Leaving them out would show a
 * net the other lines cannot explain, and anyone who reads payslips would spot it at once.
 */
final readonly class Payslip
{
    public function __construct(
        public PayslipKind $kind,
        public float $gross,
        public float $contributions,
        public float $irpef,
        public float $surtaxes,
        public float $taxFreeAdditions,
        public float $net,
    ) {}
}
