<?php

namespace App\Http\Controllers\Api;

use App\Domain\SalaryCalculator;
use App\Domain\SalaryInput;
use App\Domain\Sector;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSimulationRequest;
use App\Http\Resources\SimulationResource;
use App\Models\Simulation;
use App\Models\TaxMunicipality;
use App\Models\TaxYear;
use App\TaxTables\TaxYearRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

/**
 * Simulating is public, saving is automatic, and the year is never something the caller
 * chooses: `TaxYearRepository::currentYear()` decides it, so a request cannot ask for an
 * older, more favourable set of rates.
 */
class SimulationController extends Controller
{
    public function __construct(
        private readonly TaxYearRepository $taxYears,
        private readonly SalaryCalculator $calculator,
    ) {}

    public function store(StoreSimulationRequest $request): SimulationResource
    {
        $year = $this->taxYears->currentYear();
        $municipalityName = $request->validated('municipality');
        $sector = Sector::from($request->validated('sector'));

        $breakdown = $this->calculator->calculate(
            new SalaryInput(
                grossAnnualSalary: (float) $request->validated('gross_annual_salary'),
                monthlyPaymentsCount: (int) $request->validated('monthly_payments_count'),
                sector: $sector,
            ),
            $this->taxYears->configFor($year, $municipalityName),
        );

        $taxYear = TaxYear::query()->where('year', $year)->firstOrFail();
        $municipality = TaxMunicipality::query()
            ->where('tax_year_id', $taxYear->id)
            ->where('name', $municipalityName)
            ->firstOrFail();

        $simulation = Simulation::create([
            'token' => (string) Str::ulid(),
            'user_id' => $request->user()?->id,
            'tax_year_id' => $taxYear->id,
            'tax_municipality_id' => $municipality->id,
            'gross_annual_salary' => $breakdown->grossAnnualSalary,
            'monthly_payments_count' => $request->validated('monthly_payments_count'),
            'sector' => $sector,
            // A plain readonly object with public properties serializes through json_encode
            // exactly like an array would — this is the snapshot, taken once, at write time.
            'result' => $breakdown,
        ]);

        return new SimulationResource($simulation);
    }

    public function show(string $token): SimulationResource
    {
        return new SimulationResource(
            Simulation::query()->where('token', $token)->firstOrFail()
        );
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return SimulationResource::collection(
            $request->user()->simulations()->latest()->get()
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        // Scoping the lookup to the authenticated user's own simulations, rather than a
        // global find plus an ownership check, means a stranger's id 404s exactly like one
        // that does not exist at all — it never confirms which case it was.
        $request->user()->simulations()->findOrFail($id)->delete();

        return response()->json(status: 204);
    }
}
