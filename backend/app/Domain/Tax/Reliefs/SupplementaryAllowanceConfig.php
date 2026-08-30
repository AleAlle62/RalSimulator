<?php

namespace App\Domain\Tax\Reliefs;

/**
 * Trattamento integrativo, art. 1 D.L. 3/2020 as amended by L. 207/2024 art. 1 co. 3.
 *
 * `capacityTestReliefOffset` is the detail almost every online calculator misses: on the
 * lowest incomes the capacity test compares gross tax against the employment relief lowered
 * by this amount, not against the relief itself. It compensates the rise of that relief from
 * 1.880 to 1.955, which would otherwise have pushed the lowest incomes out of the very
 * allowance the measure exists to protect.
 */
final readonly class SupplementaryAllowanceConfig
{
    public function __construct(
        public float $fullAmountUpTo,
        public float $fullAmount,
        public float $partialUpTo,
        public float $capacityTestReliefOffset,
    ) {}
}
