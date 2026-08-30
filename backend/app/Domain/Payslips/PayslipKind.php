<?php

namespace App\Domain\Payslips;

/**
 * Ordinary payslips carry the reliefs and the local surtaxes; the extra ones do not, which is
 * why they pay out less at the same gross.
 */
enum PayslipKind: string
{
    case Ordinary = 'ordinary';
    case Thirteenth = 'thirteenth';
    case Fourteenth = 'fourteenth';
}
