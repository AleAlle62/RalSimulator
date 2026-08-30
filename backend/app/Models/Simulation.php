<?php

namespace App\Models;

use App\Domain\Sector;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'token',
    'user_id',
    'tax_year_id',
    'tax_municipality_id',
    'gross_annual_salary',
    'monthly_payments_count',
    'sector',
    'result',
])]
class Simulation extends Model
{
    /**
     * `result` is a snapshot, not a cache: it is read back as it was written and never
     * recomputed, so a shared link keeps showing the figures the sender saw even after the
     * rates behind it have moved.
     */
    protected function casts(): array
    {
        return [
            'gross_annual_salary' => 'float',
            'monthly_payments_count' => 'integer',
            'sector' => Sector::class,
            'result' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function taxYear(): BelongsTo
    {
        return $this->belongsTo(TaxYear::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(TaxMunicipality::class, 'tax_municipality_id');
    }
}
