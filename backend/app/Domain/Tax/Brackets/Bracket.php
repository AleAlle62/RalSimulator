<?php

namespace App\Domain\Tax\Brackets;

/**
 * One band of a progressive tax: the range it covers and the rate that applies inside it.
 */
final readonly class Bracket
{
    /**
     * @param  float|null  $upTo  Upper bound of the band; null means everything above.
     */
    public function __construct(
        public ?float $upTo,
        public float $rate,
    ) {}
}
