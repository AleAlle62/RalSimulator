<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `result` is passed through as it was stored: a snapshot, not something recomputed on the
 * way out. Reshaping it here would risk two different shapes for the same salary — the one
 * returned right after POST /api/simulations, and the one read back later by token.
 */
class SimulationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // Exposed because DELETE /api/me/simulations/{id} keys on it and the owner's list is
            // where that button lives. Harmless to reveal: the route is scoped to the signed-in
            // user, so knowing an id belonging to someone else buys nothing.
            'id' => $this->id,
            'token' => $this->token,
            'createdAt' => $this->created_at,
            'grossAnnualSalary' => $this->gross_annual_salary,
            'monthlyPaymentsCount' => $this->monthly_payments_count,
            'sector' => $this->sector,
            'municipality' => $this->municipality?->name,
            'region' => $this->municipality?->region?->name,
            'result' => $this->result,
        ];
    }
}
