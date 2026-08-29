<?php

namespace App\Domain\Tax;

use App\Domain\Sector;
use App\Domain\Tax\Brackets\Bracket;
use App\Domain\Tax\Contributions\ContributionsConfig;
use App\Domain\Tax\Reliefs\EmploymentReliefConfig;
use App\Domain\Tax\Reliefs\ReliefsConfig;
use App\Domain\Tax\Reliefs\SupplementaryAllowanceConfig;
use App\Domain\Tax\Reliefs\WedgeCutConfig;
use App\Domain\Tax\Surtaxes\SurtaxesConfig;

/**
 * The 2026 parameters, each one confirmed against a primary source. Nothing here is inferred
 * or interpolated; what could not be confirmed is listed under "Limiti noti" in the docs.
 *
 * Once the tax tables are seeded this stops being the production source and becomes the
 * reference the seeder is tested against: a test builds the configuration from the database
 * and asserts it equals this one, so a mistyped rate cannot pass unnoticed.
 *
 * Formulas, thresholds and every verification case: docs/FISCO-2026.md
 */
final class TaxYear2026
{
    public static function config(): TaxYearConfig
    {
        return new TaxYearConfig(
            year: 2026,
            contributions: self::contributions(),
            irpefBrackets: self::irpefBrackets(),
            reliefs: self::reliefs(),
            surtaxes: self::surtaxes(),
        );
    }

    /**
     * Source: INPS, Circolare n. 6 del 30/01/2026, contribution values from 01/01/2026.
     * IVS is 9,19% for both sectors; industry adds the 0,30% CIGS contribution.
     *
     * The 56.224 threshold is one of the figures stale calculators still report as 52.190.
     */
    private static function contributions(): ContributionsConfig
    {
        return new ContributionsConfig(
            employeeRateBySector: [
                Sector::Commerce->value => 0.0919,
                Sector::Industry->value => 0.0949,
            ],
            additionalRateThreshold: 56_224,
            additionalRate: 0.01,
            annualCeiling: 122_295,
        );
    }

    /**
     * Source: L. 199/2025 (Legge di Bilancio 2026) art. 1 co. 3, which cut the second band
     * from 35% to 33% with effect from 01/01/2026.
     *
     * @return array<int, Bracket>
     */
    private static function irpefBrackets(): array
    {
        return [
            new Bracket(upTo: 28_000, rate: 0.23),
            new Bracket(upTo: 50_000, rate: 0.33),
            new Bracket(upTo: null, rate: 0.43),
        ];
    }

    private static function reliefs(): ReliefsConfig
    {
        return new ReliefsConfig(
            // Source: art. 13 TUIR as amended for 2026. Nil above 50.000.
            employment: new EmploymentReliefConfig(
                flatAmountUpTo: 15_000,
                flatAmount: 1_955,
                firstTaperUpTo: 28_000,
                firstTaperBase: 1_910,
                firstTaperVariable: 1_190,
                secondTaperUpTo: 50_000,
                secondTaperBase: 1_910,
            ),

            // Source: taglio del cuneo fiscale, confirmed for 2026.
            wedgeCut: new WedgeCutConfig(
                exemptBonusUpTo: 20_000,
                exemptBonusBrackets: [
                    new Bracket(upTo: 8_500, rate: 0.071),
                    new Bracket(upTo: 15_000, rate: 0.053),
                    new Bracket(upTo: 20_000, rate: 0.048),
                ],
                reliefFlatUpTo: 32_000,
                reliefFlatAmount: 1_000,
                reliefTaperUpTo: 40_000,
            ),

            // Source: art. 1 D.L. 3/2020, as amended by L. 207/2024 art. 1 co. 3. The 75 is
            // the offset that keeps the lowest incomes inside the allowance after the
            // employment relief rose from 1.880 to 1.955.
            supplementaryAllowance: new SupplementaryAllowanceConfig(
                fullAmountUpTo: 15_000,
                fullAmount: 1_200,
                partialUpTo: 28_000,
                capacityTestReliefOffset: 75,
            ),
        );
    }

    /**
     * Sources: Regione Lombardia, addizionale regionale IRPEF, rates unchanged since 2022;
     * Comune di Milano, whose 23.000 threshold has been in force since 2020 and still applies
     * in 2026, no new delibera having been published. Stale calculators report it as 21.000.
     */
    private static function surtaxes(): SurtaxesConfig
    {
        return new SurtaxesConfig(
            region: 'Lombardia',
            regionalBrackets: [
                new Bracket(upTo: 15_000, rate: 0.0123),
                new Bracket(upTo: 28_000, rate: 0.0158),
                new Bracket(upTo: 50_000, rate: 0.0172),
                new Bracket(upTo: null, rate: 0.0173),
            ],
            municipality: 'Milano',
            municipalRate: 0.008,
            municipalExemptionThreshold: 23_000,
        );
    }
}
