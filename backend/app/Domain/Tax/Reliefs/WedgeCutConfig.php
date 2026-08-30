<?php

namespace App\Domain\Tax\Reliefs;

use App\Domain\Tax\Brackets\Bracket;

/**
 * The "taglio del cuneo fiscale" is two instruments under one name, mutually exclusive by
 * design. Below the threshold it is an exempt sum paid in the payslip, which never touches
 * income tax; above it, an ordinary relief that reduces the tax owed.
 */
final readonly class WedgeCutConfig
{
    /**
     * @param  float  $exemptBonusUpTo  Below this income the benefit is an exempt sum, not a relief.
     * @param  array<int, Bracket>  $exemptBonusBrackets  The band picks the rate, applied to the whole income.
     */
    public function __construct(
        public float $exemptBonusUpTo,
        public array $exemptBonusBrackets,
        public float $reliefFlatUpTo,
        public float $reliefFlatAmount,
        public float $reliefTaperUpTo,
    ) {}
}
