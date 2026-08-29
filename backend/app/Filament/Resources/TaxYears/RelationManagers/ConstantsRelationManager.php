<?php

namespace App\Filament\Resources\TaxYears\RelationManagers;

use App\TaxTables\TaxConstantKey;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The 20 constants of one tax year, shown inside it rather than as their own top level
 * resource: a constant has no meaning outside the year it belongs to.
 *
 * The key is a select over TaxConstantKey, not free text — the same guard the engine relies
 * on when it reads these rows back. It stays open to creation, not locked to the 20 the seeder
 * already wrote: updating the simulator for a new tax year is supposed to be data entry done
 * here, not a new seeder class every January.
 */
class ConstantsRelationManager extends RelationManager
{
    protected static string $relationship = 'constants';

    protected static ?string $title = 'Costanti';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('key')
                    ->label('Chiave')
                    ->options(collect(TaxConstantKey::cases())->mapWithKeys(fn ($key) => [$key->value => $key->value]))
                    ->required()
                    ->disabledOn('edit')
                    ->helperText('Non modificabile dopo la creazione: cambiarla equivarrebbe a spostare il valore su un\'altra costante.'),
                TextInput::make('value')
                    ->label('Valore')
                    ->numeric()
                    ->required(),
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
            ->modelLabel('costante')
            ->pluralModelLabel('costanti')
            ->recordTitleAttribute('key')
            ->defaultSort('key')
            ->columns([
                TextColumn::make('key')
                    ->label('Chiave')
                    ->searchable(),
                TextColumn::make('value')
                    ->label('Valore'),
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
