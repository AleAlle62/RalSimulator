<?php

namespace App\Domain\Tax\Surtaxes;

/**
 * The local surtaxes owed for the year, kept apart because the breakdown shows them as two
 * separate lines and either one can legitimately be zero.
 */
final readonly class Surtaxes
{
    public function __construct(
        public float $regional,
        public float $municipal,
        public float $total,
    ) {}
}
