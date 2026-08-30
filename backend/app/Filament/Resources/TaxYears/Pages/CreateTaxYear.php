<?php

namespace App\Filament\Resources\TaxYears\Pages;

use App\Filament\Resources\TaxYears\TaxYearResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxYear extends CreateRecord
{
    protected static string $resource = TaxYearResource::class;
}
