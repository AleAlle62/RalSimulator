<?php

namespace App\Models;

use App\TaxTables\BracketKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['tax_year_id', 'name', 'source_url', 'source_label'])]
class TaxRegion extends Model
{
    public function taxYear(): BelongsTo
    {
        return $this->belongsTo(TaxYear::class);
    }

    public function municipalities(): HasMany
    {
        return $this->hasMany(TaxMunicipality::class);
    }

    /**
     * A region carries no rate of its own: the surtax is progressive, so its bands are rows of
     * tax_brackets pointing back here. A region with none has not been researched yet — the
     * repository refuses to build a configuration from it rather than reading the silence as
     * a surtax of zero.
     */
    public function brackets(): HasMany
    {
        return $this->hasMany(TaxBracket::class, 'owner_id')
            ->where('kind', BracketKind::RegionalSurtax)
            ->orderBy('position');
    }
}
