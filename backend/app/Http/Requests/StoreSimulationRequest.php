<?php

namespace App\Http\Requests;

use App\Domain\Sector;
use App\Models\TaxYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The only shape a simulation's input ever has: gross salary, monthly payments, sector, and
 * the municipality that decides the local surtax.
 *
 * The engine does not validate — this is the boundary that does, so the calculation stays
 * pure and can be tested on values a form would reject. `municipality` is checked against the
 * currently published year specifically, not just any year on record.
 */
class StoreSimulationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gross_annual_salary' => ['required', 'numeric', 'min:0.01'],
            'monthly_payments_count' => ['required', 'integer', 'in:12,13,14'],
            'sector' => ['required', Rule::enum(Sector::class)],
            'municipality' => [
                'required',
                'string',
                Rule::exists('tax_municipalities', 'name')->where(
                    fn ($query) => $query->where('tax_year_id', $this->currentTaxYearId())
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'gross_annual_salary.required' => 'Indica la RAL lorda.',
            'gross_annual_salary.numeric' => 'La RAL dev\'essere un numero.',
            'gross_annual_salary.min' => 'La RAL dev\'essere maggiore di zero.',
            'monthly_payments_count.in' => 'Le mensilità possono essere solo 12, 13 o 14.',
            'sector.enum' => 'Il settore dev\'essere commercio o industria.',
            'municipality.exists' => 'Comune non disponibile per l\'anno in corso.',
        ];
    }

    private function currentTaxYearId(): ?int
    {
        return TaxYear::query()->published()->orderByDesc('year')->value('id');
    }
}
