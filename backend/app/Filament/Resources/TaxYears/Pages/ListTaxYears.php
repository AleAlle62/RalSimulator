<?php

namespace App\Filament\Resources\TaxYears\Pages;

use App\Filament\Resources\TaxYears\TaxYearResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTaxYears extends ListRecords
{
    protected static string $resource = TaxYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
