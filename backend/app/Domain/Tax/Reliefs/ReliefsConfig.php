<?php

namespace App\Domain\Tax\Reliefs;

/**
 * The three benefits an employee is entitled to, gathered so the calculator takes one
 * parameter instead of three.
 *
 * Only the first is a relief in the strict sense: it lowers the tax owed. The other two are
 * exempt sums paid with the salary, which never touch income tax and add to the net. They sit
 * here because deciding whether they are due is a tax computation, even though the money is not.
 */
final readonly class ReliefsConfig
{
    public function __construct(
        public EmploymentReliefConfig $employment,
        public WedgeCutConfig $wedgeCut,
        public SupplementaryAllowanceConfig $supplementaryAllowance,
    ) {}
}
