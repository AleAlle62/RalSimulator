<?php

namespace App\Domain\Tax\Surtaxes;

use App\Domain\Tax\Brackets\Brackets;

final class SurtaxesCalculator
{
    public function calculate(float $taxableIncome, SurtaxesConfig $config): Surtaxes
    {
        $regional = Brackets::apply($taxableIncome, $config->regionalBrackets);
        $municipal = $this->municipal($taxableIncome, $config);

        return new Surtaxes(
            regional: $regional,
            municipal: $municipal,
            total: $regional + $municipal,
        );
    }

    /**
     * The threshold is an exemption, not an allowance: one euro above it the rate applies to
     * the entire taxable income rather than to the excess. The step this produces in the net
     * salary curve is a genuine feature of the rules, not a rounding artefact.
     */
    private function municipal(float $taxableIncome, SurtaxesConfig $config): float
    {
        if ($taxableIncome <= $config->municipalExemptionThreshold) {
            return 0.0;
        }

        return $taxableIncome * $config->municipalRate;
    }
}
