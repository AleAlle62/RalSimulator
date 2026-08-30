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
}
