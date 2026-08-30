<?php

namespace App\Domain\Tax\Contributions;

use App\Domain\Sector;

/**
 * Employee side INPS contributions: a rate on the salary, a ceiling above which nothing more
 * is due, and an extra point on the highest incomes.
 */
final class ContributionsCalculator
{
    public function calculate(
        float $grossAnnualSalary,
        Sector $sector,
        ContributionsConfig $config,
    ): Contributions {
        $contributoryBase = $this->contributoryBase($grossAnnualSalary, $config);
        $baseRate = $config->rateFor($sector);
        $baseAmount = $contributoryBase * $baseRate;
        $additionalRateAmount = $this->additionalRateAmount($contributoryBase, $config);

        return new Contributions(
            contributoryBase: $contributoryBase,
            baseRate: $baseRate,
            baseAmount: $baseAmount,
            additionalRateAmount: $additionalRateAmount,
            total: $baseAmount + $additionalRateAmount,
        );
    }

    /** The salary is only liable up to the ceiling: earn more and nothing further is due. */
    private function contributoryBase(float $grossAnnualSalary, ContributionsConfig $config): float
    {
        return min($grossAnnualSalary, $config->annualCeiling);
    }

    /**
     * The extra point falls on the slice above the threshold, never on the whole base. Below
     * the threshold the slice is negative, and charging it would refund contributions.
     */
    private function additionalRateAmount(float $contributoryBase, ContributionsConfig $config): float
    {
        $amountAboveThreshold = max(0, $contributoryBase - $config->additionalRateThreshold);

        return $amountAboveThreshold * $config->additionalRate;
    }
}
