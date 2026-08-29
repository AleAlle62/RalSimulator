<?php

namespace App\Domain;

/**
 * What the employee declares about their contract.
 *
 * Deliberately not self validating: checking the input belongs at the edge of the
 * application, in the HTTP request, not in the engine. Keeping the two apart is what lets the
 * calculation be tested on its own, including on the absurd values a validator would reject.
 */
final readonly class SalaryInput
{
    public function __construct(
        public float $grossAnnualSalary,
        public int $monthlyPaymentsCount,
        public Sector $sector,
    ) {}
}
