<?php

namespace Database\Seeders;

use App\Models\TaxBracket;
use App\Models\TaxMunicipality;
use App\Models\TaxRegion;
use App\Models\TaxYear;
use App\TaxTables\BracketKind;
use Illuminate\Database\Seeder;

/**
 * Where you live: the regional surtax and the municipal one.
 *
 * Eight regions and the eight cities that sit in them, every one read off a primary source:
 * the municipal rate and its exemption from the MEF municipal list, each region's bands from
 * MEF's region by region page (`addregirpef.php?reg=`, one id per region, verified here
 * against Lombardia's already known figures before trusting the other seven). All eight cities
 * compute end to end.
 *
 * Torino and Genova are absent for a different reason: they charge bands of their own instead
 * of one rate behind an exemption, and the engine does not compute those yet.
 *
 * `TaxYearRepository` still refuses a region with no bands rather than reading the silence as
 * a surtax of zero — that guard has no live example left to lean on now that every seeded
 * region is complete, so the test exercises it by removing a region's bands itself.
 */
class TaxPlaces2026Seeder extends Seeder
{
    private const MEF_MUNICIPAL_LIST = 'https://www.finanze.gov.it/it/fiscalita/fiscalita-regionale-e-locale/Addizionale-comunale-allIRPEF/aliquote-applicabili/Elenchi-generali-aggiornati-quotidianamente/';

    private const MEF_MUNICIPAL_LABEL = 'MEF — elenco generale delle addizionali comunali';

    private const MEF_REGIONAL_BASE_URL = 'https://www1.finanze.gov.it/finanze2/dipartimentopolitichefiscali/fiscalitalocale/addregirpef/addregirpef.php?reg=';

    public function run(): void
    {
        $year = TaxYear::query()->where('year', 2026)->firstOrFail();

        $regions = $this->regions($year);
        $this->regionalBrackets($year, $regions);
        $this->municipalities($year, $regions);
    }

    /**
     * @return array<string, TaxRegion>
     */
    private function regions(TaxYear $year): array
    {
        $regions = [];

        foreach ($this->regionDefinitions() as $name => ['label' => $label, 'mefId' => $mefId]) {
            $regions[$name] = TaxRegion::updateOrCreate(
                ['tax_year_id' => $year->id, 'name' => $name],
                ['source_label' => $label, 'source_url' => self::MEF_REGIONAL_BASE_URL.$mefId],
            );
        }

        return $regions;
    }

    /**
     * @param  array<string, TaxRegion>  $regions
     */
    private function regionalBrackets(TaxYear $year, array $regions): void
    {
        foreach ($this->regionDefinitions() as $name => ['label' => $label, 'mefId' => $mefId, 'bands' => $bands]) {
            $region = $regions[$name];
            $url = self::MEF_REGIONAL_BASE_URL.$mefId;

            $year->brackets()
                ->where('kind', BracketKind::RegionalSurtax)
                ->where('owner_id', $region->id)
                ->delete();

            foreach ($bands as $position => [$upperBound, $rate]) {
                TaxBracket::create([
                    'tax_year_id' => $year->id,
                    'kind' => BracketKind::RegionalSurtax,
                    'owner_id' => $region->id,
                    'upper_bound' => $upperBound,
                    'rate' => $rate,
                    'position' => $position,
                    'source_label' => $label,
                    'source_url' => $url,
                ]);
            }
        }
    }

    /**
     * Every region here uses the same four boundaries (15.000 · 28.000 · 50.000) as IRPEF
     * itself, MEF's `reg=` id is the one used to build each region's precise source link, and
     * the bands are read with `apply`, sliced like IRPEF, not picked whole with `rateFor`.
     *
     * Lombardia is unchanged from the figures already verified against
     * App\Domain\Tax\TaxYear2026; fetching it again from MEF here returned the identical four
     * rates, which is what made trusting the same endpoint for the other seven worth doing.
     *
     * @return array<string, array{label: string, mefId: string, bands: array<int, array{int|null, float}>}>
     */
    private function regionDefinitions(): array
    {
        return [
            'Lombardia' => [
                'label' => 'Regione Lombardia, art. 72 c. 1 L.R. 14/07/2003 n. 10, aliquote invariate dal 2022',
                'mefId' => '10',
                'bands' => [
                    [15_000, 0.0123],
                    [28_000, 0.0158],
                    [50_000, 0.0172],
                    [null, 0.0173],
                ],
            ],
            'Lazio' => [
                // The 1,73% band stops at 15.000, not 28.000: several secondary sources report
                // the wider threshold, but MEF's own table draws the line at 15.000.
                'label' => 'Regione Lazio, L.R. 20 del 31/12/2025',
                'mefId' => '08',
                'bands' => [
                    [15_000, 0.0173],
                    [null, 0.0333],
                ],
            ],
            'Campania' => [
                'label' => 'Regione Campania, L.R. 4/2014 e L.R. 7/2022',
                'mefId' => '05',
                'bands' => [
                    [15_000, 0.0173],
                    [28_000, 0.0296],
                    [50_000, 0.0320],
                    [null, 0.0333],
                ],
            ],
            'Emilia-Romagna' => [
                'label' => 'Regione Emilia-Romagna, L.R. 19/2006 come modificata da L.R. 1/2025 e L.R. 9/2025',
                'mefId' => '06',
                'bands' => [
                    [15_000, 0.0133],
                    [28_000, 0.0193],
                    [50_000, 0.0278],
                    [null, 0.0333],
                ],
            ],
            'Toscana' => [
                'label' => 'Regione Toscana, art. 1 L.R. 48 del 28/12/2023',
                'mefId' => '17',
                'bands' => [
                    [15_000, 0.0142],
                    [28_000, 0.0143],
                    [50_000, 0.0332],
                    [null, 0.0333],
                ],
            ],
            'Puglia' => [
                // Raised by a decree covering the regional health service deficit; several
                // secondary sources still report the pre-decree 1,23%–2,23% range.
                'label' => 'Regione Puglia, decreto n. 3 del 28/05/2026 (commissario ad acta)',
                'mefId' => '14',
                'bands' => [
                    [15_000, 0.0133],
                    [28_000, 0.0213],
                    [50_000, 0.0323],
                    [null, 0.0333],
                ],
            ],
            'Veneto' => [
                // A single rate for everyone: no bands to slice, one band with no upper bound.
                // The reduced 0,9% rate for disability is out of scope, like every relief tied
                // to a dependent family member.
                'label' => 'Regione Veneto, L.R. 19/2005 art. 1 c. 5, aliquota unica',
                'mefId' => '21',
                'bands' => [
                    [null, 0.0123],
                ],
            ],
            'Sicilia' => [
                'label' => 'Regione Sicilia, L.R. 2/2007 art. 1, aliquota unica',
                'mefId' => '16',
                'bands' => [
                    [null, 0.0123],
                ],
            ],
        ];
    }

    /**
     * The threshold is an exemption, not an allowance: one euro of taxable income above it and
     * the rate applies to the whole of it. Palermo has none, which is why its column is zero
     * rather than empty.
     *
     * @param  array<string, TaxRegion>  $regions
     */
    private function municipalities(TaxYear $year, array $regions): void
    {
        foreach ($this->cities() as [$name, $province, $cadastral, $region, $rate, $exemption, $number, $date]) {
            TaxMunicipality::updateOrCreate(
                ['tax_year_id' => $year->id, 'name' => $name, 'province' => $province],
                [
                    'tax_region_id' => $regions[$region]->id,
                    'cadastral_code' => $cadastral,
                    'rate' => $rate,
                    'exemption_threshold' => $exemption,
                    'deliberation_number' => $number,
                    'deliberation_date' => $date,
                    'source_label' => self::MEF_MUNICIPAL_LABEL,
                    'source_url' => self::MEF_MUNICIPAL_LIST,
                ],
            );
        }
    }

    /**
     * @return array<int, array{string, string, string, string, float, float, string, string}>
     */
    private function cities(): array
    {
        return [
            // Milano's 23.000 exemption is the figure stale calculators still report as 21.000.
            ['Milano', 'MI', 'F205', 'Lombardia', 0.008, 23_000, '46', '2020-09-28'],
            ['Roma', 'RM', 'H501', 'Lazio', 0.009, 14_000, '186', '2024-12-19'],
            ['Napoli', 'NA', 'F839', 'Campania', 0.010, 12_000, '143', '2023-12-29'],
            ['Bologna', 'BO', 'A944', 'Emilia-Romagna', 0.008, 15_000, '354', '2016-12-22'],
            ['Firenze', 'FI', 'D612', 'Toscana', 0.002, 25_000, '47', '2014-07-28'],
            ['Bari', 'BA', 'A662', 'Puglia', 0.008, 15_000, '42', '2012-07-31'],
            ['Venezia', 'VE', 'L736', 'Veneto', 0.008, 10_000, '67', '2023-12-20'],
            ['Palermo', 'PA', 'G273', 'Sicilia', 0.01014, 0, '6', '2025-02-25'],
        ];
    }
}
