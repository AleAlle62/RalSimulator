<?php

namespace App\Domain\Tax\Contributions;

use App\Domain\Tax\Sector;

/**
 * The legal parameters of employee side INPS contributions for one tax year.
 */
final readonly class ContributionsConfig
{
    /**
     * @param  array<string, float>  $employeeRateBySector  Keyed by the Sector backing value.
     * @param  float  $additionalRateThreshold  Income above this carries an extra IVS point.
     * @param  float  $annualCeiling  Ceiling of the contributory base.
     */
    public function __construct(
        public array $employeeRateBySector,
        public float $additionalRateThreshold,
        public float $additionalRate,
        public float $annualCeiling,
    ) {}

    public function rateFor(Sector $sector): float
    {
        return $this->employeeRateBySector[$sector->value];
    }
}
