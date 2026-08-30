<?php

namespace App\Domain\Tax\Reliefs;

use App\Domain\Tax\Brackets\Brackets;

final class ReliefsCalculator
{
    /**
     * Every benefit the employee is entitled to, in one pass.
     *
     * The employment relief is computed first because the supplementary allowance needs it:
     * the entitlement is decided by comparing the tax against that relief.
     */
    public function calculate(float $taxableIncome, float $grossTax, ReliefsConfig $config): Reliefs
    {
        $employmentRelief = $this->employmentRelief($taxableIncome, $config->employment);

        return new Reliefs(
            employmentRelief: $employmentRelief,
            wedgeCutRelief: $this->wedgeCutRelief($taxableIncome, $config->wedgeCut),
            exemptWedgeCutBonus: $this->wedgeCutExemptBonus($taxableIncome, $config->wedgeCut),
            supplementaryAllowance: $this->supplementaryAllowance(
                $taxableIncome,
                $grossTax,
                $employmentRelief,
                $config->supplementaryAllowance,
            ),
        );
    }

    /**
     * Employment income relief, art. 13 TUIR: which of the four bands the income falls into.
     */
    public function employmentRelief(float $taxableIncome, EmploymentReliefConfig $config): float
    {
        if ($taxableIncome <= $config->flatAmountUpTo) {
            return $config->flatAmount;
        }

        if ($taxableIncome <= $config->firstTaperUpTo) {
            return $this->firstTaper($taxableIncome, $config);
        }

        if ($taxableIncome <= $config->secondTaperUpTo) {
            return $this->secondTaper($taxableIncome, $config);
        }

        return 0.0;
    }

    /** A share that shrinks to nothing, sitting on top of a base that never moves. */
    private function firstTaper(float $taxableIncome, EmploymentReliefConfig $config): float
    {
        $share = $this->remainingShare(
            $taxableIncome,
            $config->flatAmountUpTo,
            $config->firstTaperUpTo,
        );

        return $config->firstTaperBase + $config->firstTaperVariable * $share;
    }

    /** Here the base itself shrinks, all the way to zero. */
    private function secondTaper(float $taxableIncome, EmploymentReliefConfig $config): float
    {
        $share = $this->remainingShare(
            $taxableIncome,
            $config->firstTaperUpTo,
            $config->secondTaperUpTo,
        );

        return $config->secondTaperBase * $share;
    }

    /**
     * The wedge cut below its threshold: an exempt sum paid in the payslip, which never
     * touches income tax. The band picks a rate, and that rate applies to the whole income
     * rather than to a slice of it.
     */
    public function wedgeCutExemptBonus(float $taxableIncome, WedgeCutConfig $config): float
    {
        if ($taxableIncome > $config->exemptBonusUpTo) {
            return 0.0;
        }

        return $taxableIncome * Brackets::rateFor($taxableIncome, $config->exemptBonusBrackets);
    }

    /**
     * The wedge cut above its threshold: an ordinary relief, flat and then tapering to zero.
     * The two branches are mutually exclusive, so exactly one of them is ever non zero.
     */
    public function wedgeCutRelief(float $taxableIncome, WedgeCutConfig $config): float
    {
        if ($taxableIncome <= $config->exemptBonusUpTo) {
            return 0.0;
        }

        if ($taxableIncome <= $config->reliefFlatUpTo) {
            return $config->reliefFlatAmount;
        }

        if ($taxableIncome >= $config->reliefTaperUpTo) {
            return 0.0;
        }

        $share = $this->remainingShare($taxableIncome, $config->reliefFlatUpTo, $config->reliefTaperUpTo);

        return $config->reliefFlatAmount * $share;
    }

    /**
     * Trattamento integrativo: an exempt sum, owed only to those who actually pay tax.
     *
     * On the lowest incomes it is granted in full provided gross tax clears the capacity
     * test; on middle incomes only to the extent the employment relief outruns the gross tax.
     *
     * The gross tax has to be passed in because the entitlement is decided by looking at it.
     */
    public function supplementaryAllowance(
        float $taxableIncome,
        float $grossTax,
        float $employmentRelief,
        SupplementaryAllowanceConfig $config,
    ): float {
        if ($taxableIncome <= $config->fullAmountUpTo) {
            return $this->hasCapacity($grossTax, $employmentRelief, $config)
                ? $config->fullAmount
                : 0.0;
        }

        if ($taxableIncome <= $config->partialUpTo) {
            return $this->unusedRelief($grossTax, $employmentRelief, $config);
        }

        return 0.0;
    }

    /**
     * The bar is the employment relief lowered by the offset, not the relief itself. That
     * offset is what compensates the rise of the relief from 1.880 to 1.955: without it the
     * increase would have pushed the lowest incomes out of the allowance, and it is the
     * detail almost every online calculator gets wrong.
     */
    private function hasCapacity(
        float $grossTax,
        float $employmentRelief,
        SupplementaryAllowanceConfig $config,
    ): bool {
        return $grossTax > $employmentRelief - $config->capacityTestReliefOffset;
    }

    /**
     * How much relief the tax could not absorb, capped at the allowance.
     *
     * Only the reliefs art. 1 co. 1-bis lists count here: arts. 12, 13 and 15 co. 1 lett. a)
     * and b) TUIR. The wedge cut relief is not among them, which is why it is not subtracted.
     */
    private function unusedRelief(
        float $grossTax,
        float $employmentRelief,
        SupplementaryAllowanceConfig $config,
    ): float {
        return min($config->fullAmount, max(0, $employmentRelief - $grossTax));
    }

    /** How much of a range is still ahead: 1 at its start, 0 at its end. */
    private function remainingShare(float $income, float $from, float $to): float
    {
        return ($to - $income) / ($to - $from);
    }
}
