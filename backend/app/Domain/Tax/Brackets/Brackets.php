<?php

namespace App\Domain\Tax\Brackets;

/**
 * The two ways Italian tax rules read a list of bands.
 *
 * Every progressive tax in the simulator goes through here, so the property that earning one
 * euro more never lowers the net salary is guaranteed in one place rather than re-derived in
 * each calculator.
 */
final class Brackets
{
    /**
     * Applies each rate to its own slice of income, the way IRPEF and the regional surtax work.
     *
     * @param  array<int, Bracket>  $brackets
     */
    public static function apply(float $amount, array $brackets): float
    {
        $tax = 0.0;
        $lowerBound = 0.0;

        foreach ($brackets as $bracket) {
            if ($amount <= $lowerBound) {
                break;
            }

            $upperBound = $bracket->upTo ?? $amount;
            $taxableInBracket = min($amount, $upperBound) - $lowerBound;

            $tax += $taxableInBracket * $bracket->rate;
            $lowerBound = $upperBound;
        }

        return $tax;
    }

    /**
     * Rate of the band the amount falls into, for benefits whose percentage is chosen by
     * income band and then applied to the whole amount rather than sliced.
     *
     * @param  array<int, Bracket>  $brackets
     */
    public static function rateFor(float $amount, array $brackets): float
    {
        foreach ($brackets as $bracket) {
            if ($bracket->upTo === null || $amount <= $bracket->upTo) {
                return $bracket->rate;
            }
        }

        return 0.0;
    }
}
