<?php

namespace App\Models;

use App\TaxTables\TaxConstantKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tax_year_id', 'key', 'value', 'source_url', 'source_label'])]
class TaxConstant extends Model
{
    /**
     * Casting the key to the enum is the point of having one: a row whose key is not a name
     * the engine knows fails loudly on read instead of being silently skipped when the
     * configuration is assembled.
     */
    protected function casts(): array
    {
        return [
            'key' => TaxConstantKey::class,
            'value' => 'float',
        ];
    }

    public function taxYear(): BelongsTo
    {
        return $this->belongsTo(TaxYear::class);
    }
}
