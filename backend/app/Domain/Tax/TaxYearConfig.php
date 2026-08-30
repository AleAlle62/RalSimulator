<?php

namespace App\Domain\Tax;

use App\Domain\Tax\Brackets\Bracket;
use App\Domain\Tax\Contributions\ContributionsConfig;
use App\Domain\Tax\Reliefs\ReliefsConfig;
use App\Domain\Tax\Surtaxes\SurtaxesConfig;

/**
 * Every legal parameter the engine needs for one tax year.
 *
 * This is the single seam between the rules and where they come from. The engine is handed
 * one of these and never asks how it was built, so the same calculation runs against a
 * fixture written by hand in a test and against rows loaded from the database in production.
 */
final readonly class TaxYearConfig
{
    /**
     * @param  array<int, Bracket>  $irpefBrackets
     */
    public function __construct(
        public int $year,
        public ContributionsConfig $contributions,
        public array $irpefBrackets,
        public ReliefsConfig $reliefs,
        public SurtaxesConfig $surtaxes,
    ) {}
}
