<?php

namespace App\Domain\Tax\Reliefs;

/**
 * Employment income relief, art. 13 TUIR: a flat amount on low incomes, then two linear
 * tapers that bring it to zero at the second threshold.
 *
 * The two tapers cannot be folded into one formula: the first adds a shrinking share on top
 * of a fixed base, the second scales the base itself.
 */
final readonly class EmploymentReliefConfig
{
    public function __construct(
        public float $flatAmountUpTo,
        public float $flatAmount,
        public float $firstTaperUpTo,
        public float $firstTaperBase,
        public float $firstTaperVariable,
        public float $secondTaperUpTo,
        public float $secondTaperBase,
    ) {}
}
