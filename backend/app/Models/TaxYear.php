<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['year', 'label', 'published_at', 'notes'])]
class TaxYear extends Model
{
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * A year with no rates in it yet would compute a salary out of nothing, so the calculator
     * only ever reaches a published one. Next year's figures can be entered as the
     * deliberations arrive without becoming visible halfway through.
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    public function constants(): HasMany
    {
        return $this->hasMany(TaxConstant::class);
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(TaxBracket::class);
    }

    public function regions(): HasMany
    {
        return $this->hasMany(TaxRegion::class);
    }

    public function municipalities(): HasMany
    {
        return $this->hasMany(TaxMunicipality::class);
    }
}
