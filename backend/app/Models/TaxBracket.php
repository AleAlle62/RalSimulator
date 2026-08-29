<?php

namespace App\Models;

use App\Domain\Tax\Brackets\Bracket;
use App\TaxTables\BracketKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tax_year_id',
    'kind',
    'owner_id',
    'upper_bound',
    'rate',
    'position',
    'source_url',
    'source_label',
])]
class TaxBracket extends Model
{
    protected function casts(): array
    {
        return [
            'kind' => BracketKind::class,
            'upper_bound' => 'float',
            'rate' => 'float',
            'position' => 'integer',
        ];
    }

    public function taxYear(): BelongsTo
    {
        return $this->belongsTo(TaxYear::class);
    }

    /**
     * The row read as the domain sees it. A null upper bound is the open ended top band, which
     * is why the column is nullable rather than carrying an improbably large number.
     */
    public function toBracket(): Bracket
    {
        return new Bracket(upTo: $this->upper_bound, rate: $this->rate);
    }
}
