<?php

namespace App\Domain\Tax\Irpef;

use App\Domain\Tax\Brackets\Bracket;
use App\Domain\Tax\Brackets\Brackets;

/**
 * IRPEF before any relief: the progressive bands applied to the taxable income.
 *
 * Reliefs are computed elsewhere and subtracted by the caller, so that the step from gross to
 * net tax stays visible instead of disappearing inside this class.
 */
final class IrpefCalculator
{
    /**
     * @param  array<int, Bracket>  $brackets
     */
    public function grossTax(float $taxableIncome, array $brackets): float
    {
        return Brackets::apply($taxableIncome, $brackets);
    }

    /**
     * Rate of the highest band the income reaches. Payroll withholds the extra monthly
     * payments at this rate, since reliefs are already spent on the twelve ordinary ones.
     *
     * @param  array<int, Bracket>  $brackets
     */
    public function marginalRate(float $taxableIncome, array $brackets): float
    {
        return Brackets::rateFor($taxableIncome, $brackets);
    }
}
