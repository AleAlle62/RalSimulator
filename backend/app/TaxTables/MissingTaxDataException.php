<?php

namespace App\TaxTables;

use RuntimeException;

/**
 * Raised when the tables cannot answer a question completely.
 *
 * Every one of these could have been a default instead: a missing constant read as zero, a
 * region with no bands read as no surtax. That is precisely the failure this project exists
 * to avoid — the result would still look like a salary, plausible to the euro and wrong. A
 * simulator that refuses to answer is recoverable; one that answers wrongly is not.
 */
final class MissingTaxDataException extends RuntimeException
{
    public static function yearNotPublished(int $year): self
    {
        return new self("No published tax year for {$year}.");
    }

    public static function unknownMunicipality(int $year, string $name): self
    {
        return new self("Municipality \"{$name}\" is not among the ones seeded for {$year}.");
    }

    public static function missingConstant(int $year, TaxConstantKey $key): self
    {
        return new self("Tax year {$year} has no value for \"{$key->value}\".");
    }

    public static function missingBrackets(int $year, BracketKind $kind): self
    {
        return new self("Tax year {$year} has no {$kind->value} bands.");
    }

    /**
     * The region exists but its rates have not been researched yet. Nine tenths of the
     * regions are in this state: only Lombardia has been read off a deliberation.
     */
    public static function regionWithoutRates(string $region): self
    {
        return new self("Region \"{$region}\" has no surtax bands: its rates have not been entered yet.");
    }

    /**
     * A municipality charging bands rather than one rate. The schema holds them, the engine
     * does not compute them yet — Torino and Genova are the two that will land here first.
     */
    public static function municipalityWithoutRate(string $name): self
    {
        return new self("Municipality \"{$name}\" has no flat rate: banded municipal surtax is not supported yet.");
    }
}
