<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaxYearDetailResource;
use App\Models\TaxYear;
use App\TaxTables\BracketKind;

class TaxYearController extends Controller
{
    public function show(int $year): TaxYearDetailResource
    {
        $taxYear = TaxYear::query()
            ->published()
            ->where('year', $year)
            ->with([
                'constants',
                'brackets' => fn ($query) => $query
                    ->whereIn('kind', [BracketKind::Irpef, BracketKind::WedgeCutExemptBonus])
                    ->orderBy('kind')
                    ->orderBy('position'),
            ])
            ->firstOrFail();

        return new TaxYearDetailResource($taxYear);
    }

    /**
     * The places the wizard can offer.
     *
     * Only municipalities that actually compute are listed: a flat rate of their own, and a
     * region whose surtax bands have been entered. The others are seeded and visible in the
     * admin panel, but offering one here would end in TaxYearRepository refusing to build a
     * configuration — a dead end presented as a choice.
     */
    public function municipalities(int $year)
    {
        $taxYear = TaxYear::query()->published()->where('year', $year)->firstOrFail();

        $places = $taxYear->municipalities()
            ->whereNotNull('rate')
            ->whereHas('region.brackets')
            ->with('region:id,name')
            ->orderBy('name')
            ->get(['id', 'tax_region_id', 'name', 'province', 'rate', 'exemption_threshold']);

        return response()->json([
            'data' => $places->map(fn ($place) => [
                'name' => $place->name,
                'province' => $place->province,
                'region' => $place->region->name,
                'rate' => $place->rate,
                'exemptionThreshold' => $place->exemption_threshold,
            ]),
        ]);
    }
}
