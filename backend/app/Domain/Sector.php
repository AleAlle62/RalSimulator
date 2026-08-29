<?php

namespace App\Domain;

/**
 * The two contractual sectors the simulator covers. They differ by the CIGS contribution,
 * which industry owes and commerce does not.
 */
enum Sector: string
{
    case Commerce = 'commerce';
    case Industry = 'industry';
}
