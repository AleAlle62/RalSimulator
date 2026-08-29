<?php

namespace Database\Seeders;

use App\Models\TaxBracket;
use App\Models\TaxConstant;
use App\Models\TaxYear;
use App\TaxTables\BracketKind;
use App\TaxTables\TaxConstantKey;
use Illuminate\Database\Seeder;

/**
 * The 2026 parameters that apply to everyone, whatever their region.
 *
 * The figures are typed out here rather than read from App\Domain\Tax\TaxYear2026, and that
 * duplication is the whole point: a test builds the configuration back from these rows and
 * asserts it equals that class. Were this seeder to copy from it, the two would agree by
 * construction and the test would be theatre — it could never catch the mistyped rate it
 * exists to catch.
 *
 * Rerunnable: constants are matched on their key, bands are replaced.
 */
class TaxYear2026Seeder extends Seeder
{
    private const CONTRIBUTIONS = 'INPS, circolare n. 6 del 30/01/2026';

    private const CONTRIBUTIONS_URL = 'https://www.inps.it/it/it/inps-comunica/atti/circolari-messaggi-e-normativa/dettaglio.circolari-e-messaggi.2026.01.circolare-numero-6-del-30-01-2026_15151.html';

    private const EMPLOYMENT_RELIEF = 'Art. 13 TUIR, testo in vigore dal 01/01/2026';

    private const EMPLOYMENT_RELIEF_URL = 'https://www.normattiva.it/uri-res/N2Ls?urn:nir:presidente.repubblica:decreto:1986;917';

    /**
     * Cited as the 2025 budget law, not the 2026 one: this is where the exempt bonus and the
     * relief were legislated (art. 1 commi 4 e 6), and the measure was made structural, with
     * no sunset clause. The 2026 budget law leaves the figures untouched; a fresh citation
     * every year would misstate which law actually set the numbers.
     */
    private const WEDGE_CUT = 'L. 207/2024 art. 1 commi 4 e 6 — taglio del cuneo fiscale, misura strutturale';

    private const WEDGE_CUT_URL = 'https://www.normattiva.it/uri-res/N2Ls?urn:nir:stato:legge:2024-12-30;207';

    private const SUPPLEMENTARY = 'Art. 1 D.L. 3/2020, come modificato da L. 207/2024 art. 1 co. 3';

    private const SUPPLEMENTARY_URL = 'https://www.normattiva.it/uri-res/N2Ls?urn:nir:stato:decreto.legge:2020-02-05;3';

    private const IRPEF = 'L. 199/2025 (Legge di Bilancio 2026) art. 1 co. 3';

    private const IRPEF_URL = 'https://www.normattiva.it/uri-res/N2Ls?urn:nir:stato:legge:2025-12-30;199';

    public function run(): void
    {
        $year = TaxYear::updateOrCreate(
            ['year' => 2026],
            [
                'label' => 'Anno d\'imposta 2026',
                'published_at' => now(),
                'notes' => 'Lavoro dipendente, nessun familiare a carico e nessun onere detraibile.',
            ],
        );

        $this->constants($year);
        $this->irpefBrackets($year);
        $this->wedgeCutBrackets($year);
    }

    private function constants(TaxYear $year): void
    {
        foreach ($this->values() as [$key, $value, $label, $url]) {
            TaxConstant::updateOrCreate(
                ['tax_year_id' => $year->id, 'key' => $key],
                ['value' => $value, 'source_label' => $label, 'source_url' => $url],
            );
        }
    }

    /**
     * @return array<int, array{TaxConstantKey, float, string, string}>
     */
    private function values(): array
    {
        return [
            // IVS is 9,19% in both sectors; industry adds the 0,30% CIGS contribution. The
            // 56.224 threshold is the figure stale calculators still report as 52.190.
            [TaxConstantKey::ContributionRateCommerce, 0.0919, self::CONTRIBUTIONS, self::CONTRIBUTIONS_URL],
            [TaxConstantKey::ContributionRateIndustry, 0.0949, self::CONTRIBUTIONS, self::CONTRIBUTIONS_URL],
            [TaxConstantKey::ContributionAdditionalRate, 0.01, self::CONTRIBUTIONS, self::CONTRIBUTIONS_URL],
            [TaxConstantKey::ContributionAdditionalRateThreshold, 56_224, self::CONTRIBUTIONS, self::CONTRIBUTIONS_URL],
            [TaxConstantKey::ContributionAnnualCeiling, 122_295, self::CONTRIBUTIONS, self::CONTRIBUTIONS_URL],

            [TaxConstantKey::EmploymentReliefFlatUpTo, 15_000, self::EMPLOYMENT_RELIEF, self::EMPLOYMENT_RELIEF_URL],
            [TaxConstantKey::EmploymentReliefFlatAmount, 1_955, self::EMPLOYMENT_RELIEF, self::EMPLOYMENT_RELIEF_URL],
            [TaxConstantKey::EmploymentReliefFirstTaperUpTo, 28_000, self::EMPLOYMENT_RELIEF, self::EMPLOYMENT_RELIEF_URL],
            [TaxConstantKey::EmploymentReliefFirstTaperBase, 1_910, self::EMPLOYMENT_RELIEF, self::EMPLOYMENT_RELIEF_URL],
            [TaxConstantKey::EmploymentReliefFirstTaperVariable, 1_190, self::EMPLOYMENT_RELIEF, self::EMPLOYMENT_RELIEF_URL],
            [TaxConstantKey::EmploymentReliefSecondTaperUpTo, 50_000, self::EMPLOYMENT_RELIEF, self::EMPLOYMENT_RELIEF_URL],
            [TaxConstantKey::EmploymentReliefSecondTaperBase, 1_910, self::EMPLOYMENT_RELIEF, self::EMPLOYMENT_RELIEF_URL],

            [TaxConstantKey::WedgeCutExemptBonusUpTo, 20_000, self::WEDGE_CUT, self::WEDGE_CUT_URL],
            [TaxConstantKey::WedgeCutReliefFlatUpTo, 32_000, self::WEDGE_CUT, self::WEDGE_CUT_URL],
            [TaxConstantKey::WedgeCutReliefFlatAmount, 1_000, self::WEDGE_CUT, self::WEDGE_CUT_URL],
            [TaxConstantKey::WedgeCutReliefTaperUpTo, 40_000, self::WEDGE_CUT, self::WEDGE_CUT_URL],

            // The 75 is the offset in the capacity test: on the lowest incomes the allowance
            // is compared against the employment relief lowered by this much, not against the
            // relief itself. It is what almost every online calculator gets wrong.
            [TaxConstantKey::SupplementaryAllowanceFullUpTo, 15_000, self::SUPPLEMENTARY, self::SUPPLEMENTARY_URL],
            [TaxConstantKey::SupplementaryAllowanceFullAmount, 1_200, self::SUPPLEMENTARY, self::SUPPLEMENTARY_URL],
            [TaxConstantKey::SupplementaryAllowancePartialUpTo, 28_000, self::SUPPLEMENTARY, self::SUPPLEMENTARY_URL],
            [TaxConstantKey::SupplementaryAllowanceCapacityTestReliefOffset, 75, self::SUPPLEMENTARY, self::SUPPLEMENTARY_URL],
        ];
    }

    /** The second band fell from 35% to 33% with effect from 01/01/2026. */
    private function irpefBrackets(TaxYear $year): void
    {
        $this->replaceBrackets($year, BracketKind::Irpef, self::IRPEF, self::IRPEF_URL, [
            [28_000, 0.23],
            [50_000, 0.33],
            [null, 0.43],
        ]);
    }

    /**
     * The band picks the rate, which is then applied to the whole income rather than sliced —
     * so these are read with rateFor, not with apply.
     */
    private function wedgeCutBrackets(TaxYear $year): void
    {
        $this->replaceBrackets($year, BracketKind::WedgeCutExemptBonus, self::WEDGE_CUT, self::WEDGE_CUT_URL, [
            [8_500, 0.071],
            [15_000, 0.053],
            [20_000, 0.048],
        ]);
    }

    /**
     * Replaced rather than matched one by one: bands have no natural key, and a list that
     * shrank between two runs would otherwise leave an orphan band behind — which would be
     * read as part of the scale and quietly change the tax.
     *
     * @param  array<int, array{float|null, float}>  $bands
     */
    private function replaceBrackets(TaxYear $year, BracketKind $kind, string $label, string $url, array $bands): void
    {
        $year->brackets()->where('kind', $kind)->whereNull('owner_id')->delete();

        foreach ($bands as $position => [$upperBound, $rate]) {
            TaxBracket::create([
                'tax_year_id' => $year->id,
                'kind' => $kind,
                'upper_bound' => $upperBound,
                'rate' => $rate,
                'position' => $position,
                'source_label' => $label,
                'source_url' => $url,
            ]);
        }
    }
}
