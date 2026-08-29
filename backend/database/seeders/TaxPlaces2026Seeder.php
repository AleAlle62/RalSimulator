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
 * Eight cities are seeded with rates read off the MEF list, each with the deliberation that
 * set it. Their regions are seeded too, but only **Lombardia** carries bands: it is the one
 * region whose rates have been verified. The other seven are rows with a name and a place to
 * look, and nothing else.
 *
 * That gap is deliberate and enforced. Seeding a region with no bands and letting the
 * calculator read the silence as a surtax of zero would produce a net salary that is wrong by
 * a few hundred euro and looks perfectly ordinary; the repository refuses instead. So today
 * only Milano computes end to end, and the other seven wait for their region — which is a
 * data entry job, not a code change.
 *
 * Torino and Genova are absent for a different reason: they charge bands of their own, and
 * the engine still treats the municipal surtax as one rate behind an exemption.
 */
class TaxPlaces2026Seeder extends Seeder
{
    private const MEF_MUNICIPAL_LIST = 'https://www.finanze.gov.it/it/fiscalita/fiscalita-regionale-e-locale/Addizionale-comunale-allIRPEF/aliquote-applicabili/Elenchi-generali-aggiornati-quotidianamente/';

    private const MEF_REGIONAL_LIST = 'https://www1.finanze.gov.it/finanze2/dipartimentopolitichefiscali/fiscalitalocale/addregirpef/sceltaregione.htm';

    private const MEF_MUNICIPAL_LABEL = 'MEF — elenco generale delle addizionali comunali';

    private const MEF_REGIONAL_LABEL = 'MEF — addizionali regionali IRPEF';

    private const LOMBARDIA_LABEL = 'Regione Lombardia, addizionale regionale IRPEF, aliquote invariate dal 2022';

    public function run(): void
    {
        $year = TaxYear::query()->where('year', 2026)->firstOrFail();

        $regions = $this->regions($year);
        $this->lombardiaBrackets($year, $regions['Lombardia']);
        $this->municipalities($year, $regions);
    }

    /**
     * @return array<string, TaxRegion>
     */
    private function regions(TaxYear $year): array
    {
        $names = [
            'Lombardia' => self::LOMBARDIA_LABEL,
            'Lazio' => self::MEF_REGIONAL_LABEL,
            'Campania' => self::MEF_REGIONAL_LABEL,
            'Emilia-Romagna' => self::MEF_REGIONAL_LABEL,
            'Toscana' => self::MEF_REGIONAL_LABEL,
            'Puglia' => self::MEF_REGIONAL_LABEL,
            'Veneto' => self::MEF_REGIONAL_LABEL,
            'Sicilia' => self::MEF_REGIONAL_LABEL,
        ];

        $regions = [];

        foreach ($names as $name => $label) {
            $regions[$name] = TaxRegion::updateOrCreate(
                ['tax_year_id' => $year->id, 'name' => $name],
                ['source_label' => $label, 'source_url' => self::MEF_REGIONAL_LIST],
            );
        }

        return $regions;
    }

    /** Progressive and sliced, exactly like IRPEF: each rate applies to its own band. */
    private function lombardiaBrackets(TaxYear $year, TaxRegion $lombardia): void
    {
        $bands = [
            [15_000, 0.0123],
            [28_000, 0.0158],
            [50_000, 0.0172],
            [null, 0.0173],
        ];

        $year->brackets()
            ->where('kind', BracketKind::RegionalSurtax)
            ->where('owner_id', $lombardia->id)
            ->delete();

        foreach ($bands as $position => [$upperBound, $rate]) {
            TaxBracket::create([
                'tax_year_id' => $year->id,
                'kind' => BracketKind::RegionalSurtax,
                'owner_id' => $lombardia->id,
                'upper_bound' => $upperBound,
                'rate' => $rate,
                'position' => $position,
                'source_label' => self::LOMBARDIA_LABEL,
                'source_url' => self::MEF_REGIONAL_LIST,
            ]);
        }
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
