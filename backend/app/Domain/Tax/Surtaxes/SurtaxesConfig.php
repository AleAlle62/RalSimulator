<?php

namespace App\Domain\Tax\Surtaxes;

use App\Domain\Tax\Brackets\Bracket;

/**
 * The two local surtaxes withheld on top of income tax, region and municipality.
 *
 * They work differently on purpose: the regional one is progressive like income tax, while
 * the municipal one is a flat rate behind an exemption.
 */
final readonly class SurtaxesConfig
{
    /**
     * @param  array<int, Bracket>  $regionalBrackets  Progressive, like income tax.
     * @param  float  $municipalExemptionThreshold  Below it nothing is owed at all.
     */
    public function __construct(
        public string $region,
        public array $regionalBrackets,
        public string $municipality,
        public float $municipalRate,
        public float $municipalExemptionThreshold,
    ) {}
}
