<?php

namespace App\TaxTables;

use App\Domain\Sector;
use App\Domain\Tax\Brackets\Bracket;
use App\Domain\Tax\Contributions\ContributionsConfig;
use App\Domain\Tax\Reliefs\EmploymentReliefConfig;
use App\Domain\Tax\Reliefs\ReliefsConfig;
use App\Domain\Tax\Reliefs\SupplementaryAllowanceConfig;
use App\Domain\Tax\Reliefs\WedgeCutConfig;
use App\Domain\Tax\Surtaxes\SurtaxesConfig;
use App\Domain\Tax\TaxYearConfig;
use App\Models\TaxMunicipality;
use App\Models\TaxYear;

/**
 * Rebuilds a TaxYearConfig out of the stored tables.
 *
 * This is the only place that knows the rates were ever rows. The engine receives the same
 * object whether it was assembled here or written by hand in a test, which is what lets the
 * calculation be tested without a database and run in production without a second set of
 * numbers to keep in step.
 *
 * A configuration is always built for one municipality, because the surtaxes are part of it:
 * asking for "the 2026 rates" without saying where is not a complete question.
 */
final class TaxYearRepository
{
    /**
     * The year a new simulation uses. Never taken from the request: a client choosing its own
     * year could pick an older, more favourable one, and the whole point of versioning by year
     * is that nobody outside this table gets to make that choice.
     */
    public function currentYear(): int
    {
        $year = TaxYear::query()->published()->max('year');

        return $year ?? throw MissingTaxDataException::noPublishedYear();
    }

    public function configFor(int $year, string $municipality): TaxYearConfig
    {
        $taxYear = TaxYear::query()->published()->where('year', $year)->first();

        if ($taxYear === null) {
            throw MissingTaxDataException::yearNotPublished($year);
        }

        $place = $taxYear->municipalities()->with('region.brackets')->where('name', $municipality)->first();

        if ($place === null) {
            throw MissingTaxDataException::unknownMunicipality($year, $municipality);
        }

        $constants = $this->constants($taxYear);

        return new TaxYearConfig(
            year: $taxYear->year,
            contributions: $this->contributions($constants, $taxYear->year),
            irpefBrackets: $this->brackets($taxYear, BracketKind::Irpef),
            reliefs: $this->reliefs($constants, $taxYear),
            surtaxes: $this->surtaxes($place),
        );
    }

    /**
     * Loaded in one query and handed around as an array: twenty figures are cheaper to keep
     * in memory than to fetch one at a time, and it keeps the assembly below free of queries.
     *
     * @return array<string, float>
     */
    private function constants(TaxYear $taxYear): array
    {
        return $taxYear->constants
            ->mapWithKeys(fn ($constant) => [$constant->key->value => $constant->value])
            ->all();
    }

    /**
     * @param  array<string, float>  $constants
     */
    private function value(array $constants, TaxConstantKey $key, int $year): float
    {
        return $constants[$key->value] ?? throw MissingTaxDataException::missingConstant($year, $key);
    }

    /**
     * @return array<int, Bracket>
     */
    private function brackets(TaxYear $taxYear, BracketKind $kind, ?int $ownerId = null): array
    {
        $rows = $taxYear->brackets()
            ->where('kind', $kind)
            ->when($ownerId === null, fn ($query) => $query->whereNull('owner_id'))
            ->when($ownerId !== null, fn ($query) => $query->where('owner_id', $ownerId))
            ->orderBy('position')
            ->get();

        if ($rows->isEmpty()) {
            throw MissingTaxDataException::missingBrackets($taxYear->year, $kind);
        }

        return $rows->map(fn ($row) => $row->toBracket())->all();
    }

    /**
     * @param  array<string, float>  $constants
     */
    private function contributions(array $constants, int $year): ContributionsConfig
    {
        return new ContributionsConfig(
            employeeRateBySector: [
                Sector::Commerce->value => $this->value($constants, TaxConstantKey::ContributionRateCommerce, $year),
                Sector::Industry->value => $this->value($constants, TaxConstantKey::ContributionRateIndustry, $year),
            ],
            additionalRateThreshold: $this->value($constants, TaxConstantKey::ContributionAdditionalRateThreshold, $year),
            additionalRate: $this->value($constants, TaxConstantKey::ContributionAdditionalRate, $year),
            annualCeiling: $this->value($constants, TaxConstantKey::ContributionAnnualCeiling, $year),
        );
    }

    /**
     * @param  array<string, float>  $constants
     */
    private function reliefs(array $constants, TaxYear $taxYear): ReliefsConfig
    {
        $year = $taxYear->year;

        return new ReliefsConfig(
            employment: new EmploymentReliefConfig(
                flatAmountUpTo: $this->value($constants, TaxConstantKey::EmploymentReliefFlatUpTo, $year),
                flatAmount: $this->value($constants, TaxConstantKey::EmploymentReliefFlatAmount, $year),
                firstTaperUpTo: $this->value($constants, TaxConstantKey::EmploymentReliefFirstTaperUpTo, $year),
                firstTaperBase: $this->value($constants, TaxConstantKey::EmploymentReliefFirstTaperBase, $year),
                firstTaperVariable: $this->value($constants, TaxConstantKey::EmploymentReliefFirstTaperVariable, $year),
                secondTaperUpTo: $this->value($constants, TaxConstantKey::EmploymentReliefSecondTaperUpTo, $year),
                secondTaperBase: $this->value($constants, TaxConstantKey::EmploymentReliefSecondTaperBase, $year),
            ),
            wedgeCut: new WedgeCutConfig(
                exemptBonusUpTo: $this->value($constants, TaxConstantKey::WedgeCutExemptBonusUpTo, $year),
                exemptBonusBrackets: $this->brackets($taxYear, BracketKind::WedgeCutExemptBonus),
                reliefFlatUpTo: $this->value($constants, TaxConstantKey::WedgeCutReliefFlatUpTo, $year),
                reliefFlatAmount: $this->value($constants, TaxConstantKey::WedgeCutReliefFlatAmount, $year),
                reliefTaperUpTo: $this->value($constants, TaxConstantKey::WedgeCutReliefTaperUpTo, $year),
            ),
            supplementaryAllowance: new SupplementaryAllowanceConfig(
                fullAmountUpTo: $this->value($constants, TaxConstantKey::SupplementaryAllowanceFullUpTo, $year),
                fullAmount: $this->value($constants, TaxConstantKey::SupplementaryAllowanceFullAmount, $year),
                partialUpTo: $this->value($constants, TaxConstantKey::SupplementaryAllowancePartialUpTo, $year),
                capacityTestReliefOffset: $this->value($constants, TaxConstantKey::SupplementaryAllowanceCapacityTestReliefOffset, $year),
            ),
        );
    }

    private function surtaxes(TaxMunicipality $place): SurtaxesConfig
    {
        $region = $place->region;

        if ($region->brackets->isEmpty()) {
            throw MissingTaxDataException::regionWithoutRates($region->name);
        }

        if ($place->rate === null) {
            throw MissingTaxDataException::municipalityWithoutRate($place->name);
        }

        return new SurtaxesConfig(
            region: $region->name,
            regionalBrackets: $region->brackets->map(fn ($row) => $row->toBracket())->all(),
            municipality: $place->name,
            municipalRate: $place->rate,
            municipalExemptionThreshold: $place->exemption_threshold,
        );
    }
}
