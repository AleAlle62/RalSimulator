<?php

namespace App\Domain\Payslips;

/**
 * The year's payslips. The twelve ordinary ones are identical, so one stands for all of them;
 * the extras are listed because they differ from the ordinary one and from each other's kind.
 */
final readonly class PayslipSchedule
{
    /**
     * @param  array<int, Payslip>  $extras  Thirteenth and fourteenth, when the contract has them.
     */
    public function __construct(
        public Payslip $ordinary,
        public array $extras,
    ) {}
}
