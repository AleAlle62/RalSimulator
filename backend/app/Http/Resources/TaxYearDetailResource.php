<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The constants and national bands of one tax year, each with its source — what the "riga per
 * riga" screen cites next to the numbers. Deliberately national only: a simulation's own
 * regional and municipal surtax already travels inside its own `result`, so repeating it here
 * would be the same figures with no year-vs-place question left for the caller to ask.
 */
class TaxYearDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'year' => $this->year,
            'label' => $this->label,
            'publishedAt' => $this->published_at,
            'constants' => $this->constants->map(fn ($constant) => [
                'key' => $constant->key,
                'value' => $constant->value,
                'sourceLabel' => $constant->source_label,
                'sourceUrl' => $constant->source_url,
            ]),
            'brackets' => $this->brackets->map(fn ($bracket) => [
                'kind' => $bracket->kind,
                'upperBound' => $bracket->upper_bound,
                'rate' => $bracket->rate,
                'position' => $bracket->position,
                'sourceLabel' => $bracket->source_label,
                'sourceUrl' => $bracket->source_url,
            ]),
        ];
    }
}
