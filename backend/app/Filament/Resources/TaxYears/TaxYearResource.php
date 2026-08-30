<?php

namespace App\Filament\Resources\TaxYears;

use App\Filament\Resources\TaxYears\Pages\CreateTaxYear;
use App\Filament\Resources\TaxYears\Pages\EditTaxYear;
use App\Filament\Resources\TaxYears\Pages\ListTaxYears;
use App\Filament\Resources\TaxYears\RelationManagers\BracketsRelationManager;
use App\Filament\Resources\TaxYears\RelationManagers\ConstantsRelationManager;
use App\Filament\Resources\TaxYears\RelationManagers\MunicipalitiesRelationManager;
use App\Filament\Resources\TaxYears\RelationManagers\RegionsRelationManager;
use App\Models\TaxYear;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaxYearResource extends Resource
{
    protected static ?string $model = TaxYear::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return 'anno fiscale';
    }

    public static function getPluralModelLabel(): string
    {
        return 'anni fiscali';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('year')
                    ->label('Anno')
                    ->required()
                    ->numeric(),
                TextInput::make('label')
                    ->label('Etichetta')
                    ->required(),
                DateTimePicker::make('published_at')
                    ->label('Pubblicato il')
                    ->helperText('Finché è vuoto, il calcolatore non vede questo anno: i dati si possono inserire man mano che arrivano le delibere, senza che siano già in uso.'),
                Textarea::make('notes')
                    ->label('Note')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('year')
                    ->label('Anno')
                    ->sortable(),
                TextColumn::make('label')
                    ->label('Etichetta')
                    ->searchable(),
                TextColumn::make('published_at')
                    ->label('Pubblicato il')
                    ->dateTime()
                    ->placeholder('non pubblicato')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Aggiornato il')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ConstantsRelationManager::class,
            RegionsRelationManager::class,
            MunicipalitiesRelationManager::class,
            BracketsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxYears::route('/'),
            'create' => CreateTaxYear::route('/create'),
            'edit' => EditTaxYear::route('/{record}/edit'),
        ];
    }
}
