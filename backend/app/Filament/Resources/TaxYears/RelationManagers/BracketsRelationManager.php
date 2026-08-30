<?php

namespace App\Filament\Resources\TaxYears\RelationManagers;

use App\Models\TaxMunicipality;
use App\Models\TaxRegion;
use App\TaxTables\BracketKind;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Every band the engine reads: IRPEF and the wedge cut are national (`owner_id` empty), the
 * regional surtax belongs to a region, the municipal one — not seeded yet, schema-ready only —
 * to a municipality. One table for all four, `kind` says which.
 *
 * `position` is the field most likely to be misused here: read out of order, a scale computes
 * a plausible and wrong tax rather than an error, which is why the form spells that out rather
 * than leaving `position` looking like a harmless sort hint.
 */
class BracketsRelationManager extends RelationManager
{
    protected static string $relationship = 'brackets';

    protected static ?string $title = 'Scaglioni';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('kind')
                    ->label('Tipo')
                    ->options(collect(BracketKind::cases())->mapWithKeys(fn ($kind) => [$kind->value => $kind->value]))
                    ->required()
                    ->live(),
                Select::make('owner_id')
                    ->label('Regione o comune')
                    ->options(fn (Get $get) => match ($get('kind')) {
                        BracketKind::RegionalSurtax->value => $this->getOwnerRecord()->regions()->pluck('name', 'id'),
                        BracketKind::MunicipalSurtax->value => $this->getOwnerRecord()->municipalities()->pluck('name', 'id'),
                        default => [],
                    })
                    ->visible(fn (Get $get) => in_array($get('kind'), [BracketKind::RegionalSurtax->value, BracketKind::MunicipalSurtax->value], true))
                    ->required(fn (Get $get) => in_array($get('kind'), [BracketKind::RegionalSurtax->value, BracketKind::MunicipalSurtax->value], true)),
                TextInput::make('upper_bound')
                    ->label('Limite superiore')
                    ->numeric()
                    ->helperText('Vuoto per l\'ultima fascia, quella senza limite superiore.'),
                TextInput::make('rate')
                    ->label('Aliquota')
                    ->numeric()
                    ->required(),
                TextInput::make('position')
                    ->label('Posizione')
                    ->numeric()
                    ->required()
                    ->helperText('L\'ordine in cui il motore legge le fasce. Sbagliata, calcola una tassa plausibile e sbagliata: non dà un errore.'),
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
            ->modelLabel('scaglione')
            ->pluralModelLabel('scaglioni')
            ->recordTitleAttribute('kind')
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('kind')->orderBy('owner_id')->orderBy('position'))
            ->columns([
                TextColumn::make('kind')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('owner')
                    ->label('Ambito')
                    ->getStateUsing(fn ($record) => match ($record->kind) {
                        BracketKind::RegionalSurtax => TaxRegion::find($record->owner_id)?->name,
                        BracketKind::MunicipalSurtax => TaxMunicipality::find($record->owner_id)?->name,
                        default => 'nazionale',
                    }),
                TextColumn::make('position')
                    ->label('Pos.'),
                TextColumn::make('upper_bound')
                    ->label('Fino a')
                    ->formatStateUsing(fn (?float $state) => $state !== null ? number_format($state, 0, ',', '.').' €' : null)
                    ->placeholder('senza limite'),
                TextColumn::make('rate')
                    ->label('Aliquota')
                    ->formatStateUsing(fn (float $state) => number_format($state * 100, 3).' %'),
                TextColumn::make('source_label')
                    ->label('Fonte')
                    ->limit(40),
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
