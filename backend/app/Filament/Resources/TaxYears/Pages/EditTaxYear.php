<?php

namespace App\Filament\Resources\TaxYears\Pages;

use App\Filament\Resources\TaxYears\TaxYearResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaxYear extends EditRecord
{
    protected static string $resource = TaxYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
