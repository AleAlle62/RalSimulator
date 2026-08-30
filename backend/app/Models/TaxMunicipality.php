<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tax_year_id',
    'tax_region_id',
    'name',
    'province',
    'cadastral_code',
    'rate',
    'exemption_threshold',
    'deliberation_number',
    'deliberation_date',
    'source_url',
    'source_label',
])]
class TaxMunicipality extends Model
{
    protected function casts(): array
    {
        return [
            'rate' => 'float',
            'exemption_threshold' => 'float',
            'deliberation_date' => 'date',
        ];
    }

    public function taxYear(): BelongsTo
    {
        return $this->belongsTo(TaxYear::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(TaxRegion::class, 'tax_region_id');
    }
}
