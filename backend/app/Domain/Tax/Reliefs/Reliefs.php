<?php

namespace App\Domain\Tax\Reliefs;

/**
 * What the employee is actually entitled to, split by where the money lands.
 *
 * The distinction is the one thing that must not be blurred: two of these lower the tax, the
 * other two arrive in the payslip untaxed. Adding a sum to the wrong group changes the net
 * salary, and it is the mistake most online calculators make.
 */
final readonly class Reliefs
{
    public function __construct(
        public float $employmentRelief,
        public float $wedgeCutRelief,
        public float $exemptWedgeCutBonus,
        public float $supplementaryAllowance,
    ) {}

    /** Reliefs that reduce the tax owed. Never more than the tax itself: the excess is lost. */
    public function totalDeductedFromTax(): float
    {
        return $this->employmentRelief + $this->wedgeCutRelief;
    }

    /**
     * Sums paid with the salary that are never taxed. They raise the net without ever having
     * been part of the gross, which is why the net cannot be read as a slice of the RAL alone.
     */
    public function taxFreeAdditions(): float
    {
        return $this->exemptWedgeCutBonus + $this->supplementaryAllowance;
    }
}
