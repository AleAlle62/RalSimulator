<?php

namespace App\TaxTables;

/**
 * Which list of bands a row of `tax_brackets` belongs to.
 *
 * The first two are national and stand alone; the last two belong to a place, and carry the
 * id of the region or municipality in `owner_id`.
 */
enum BracketKind: string
{
    case Irpef = 'irpef';
    case WedgeCutExemptBonus = 'wedge_cut_exempt_bonus';
    case RegionalSurtax = 'regional_surtax';

    /**
     * For the minority of municipalities that charge bands instead of a single rate. The
     * schema accepts them; the engine does not compute them yet, so nothing is seeded here.
     */
    case MunicipalSurtax = 'municipal_surtax';

    public function belongsToAPlace(): bool
    {
        return $this === self::RegionalSurtax || $this === self::MunicipalSurtax;
    }
}
