<?php

namespace App\Filament\Resources\TaxYears\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The municipalities of one tax year, each pointing at one of the regions on the Regioni tab.
 *
 * `rate` is left optional in the form, mirroring the schema: a municipality with bands of its
 * own instead of one rate behind an exemption has no single number to put here, and the engine
 * does not compute that case yet. An empty rate is a known gap, not a mistake.
 */
class MunicipalitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'municipalities';

    protected static ?string $title = 'Comuni';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tax_region_id')
                    ->label('Regione')
                    ->options(fn () => $this->getOwnerRecord()->regions()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),
                TextInput::make('province')
                    ->label('Provincia')
                    ->required()
                    ->maxLength(2)
                    ->dehydrateStateUsing(fn (?string $state) => $state !== null ? strtoupper($state) : null),
                TextInput::make('cadastral_code')
                    ->label('Codice catastale')
                    ->maxLength(4),
                TextInput::make('rate')
                    ->label('Aliquota')
                    ->numeric()
                    ->helperText('Vuota se il comune usa scaglioni propri invece di un\'aliquota unica: caso che il motore non gestisce ancora.'),
                TextInput::make('exemption_threshold')
                    ->label('Soglia di esenzione')
                    ->numeric()
                    ->required()
                    ->default(0),
                TextInput::make('deliberation_number')
                    ->label('Numero delibera'),
                DatePicker::make('deliberation_date')
                    ->label('Data delibera'),
                TextInput::make('source_label')
                    ->label('Fonte'),
                TextInput::make('source_url')
                    ->label('Link alla fonte')
                    ->url(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('comune')
            ->pluralModelLabel('comuni')
            ->recordTitleAttribute('name')
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('province')
                    ->label('Prov.'),
                TextColumn::make('region.name')
                    ->label('Regione'),
                TextColumn::make('rate')
                    ->label('Aliquota')
                    ->formatStateUsing(fn (?float $state) => $state !== null ? number_format($state * 100, 3).' %' : null)
                    ->placeholder('a scaglioni'),
                TextColumn::make('exemption_threshold')
                    ->label('Esenzione')
                    ->formatStateUsing(fn (float $state) => number_format($state, 0, ',', '.').' €'),
                TextColumn::make('deliberation_number')
                    ->label('Delibera')
                    ->formatStateUsing(fn (?string $state, $record) => $state !== null
                        ? "n. {$state} · ".$record->deliberation_date?->format('d/m/Y')
                        : null)
                    ->placeholder('—'),
                TextColumn::make('source_url')
                    ->label('Link')
                    ->url(fn ($record) => $record->source_url)
                    ->openUrlInNewTab()
                    ->limit(30)
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
