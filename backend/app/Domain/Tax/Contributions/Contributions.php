<?php

namespace App\Domain\Tax\Contributions;

/**
 * What the employee actually pays in INPS contributions, kept split into the parts the
 * line by line breakdown has to show.
 */
final readonly class Contributions
{
    /**
     * @param  float  $contributoryBase  Gross salary actually subject to contributions, after the ceiling.
     */
    public function __construct(
        public float $contributoryBase,
        public float $baseRate,
        public float $baseAmount,
        public float $additionalRateAmount,
        public float $total,
    ) {}
}
