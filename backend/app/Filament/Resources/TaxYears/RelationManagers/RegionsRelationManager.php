<?php

namespace App\Filament\Resources\TaxYears\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The regions of one tax year. A region carries no rate of its own — its surtax bands live in
 * tax_brackets — so this manager only holds identity and source; the bands are edited from the
 * Scaglioni tab, scoped by region there.
 *
 * The municipality and bracket counts are shown because deleting a region cascades to both:
 * seeing "3 comuni" next to a row is the warning before that becomes irreversible.
 */
class RegionsRelationManager extends RelationManager
{
    protected static string $relationship = 'regions';

    protected static ?string $title = 'Regioni';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
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
            ->modelLabel('regione')
            ->pluralModelLabel('regioni')
            ->recordTitleAttribute('name')
            ->defaultSort('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['brackets', 'municipalities']))
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('brackets_count')
                    ->label('Scaglioni')
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'danger'),
                TextColumn::make('municipalities_count')
                    ->label('Comuni')
                    ->badge(),
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
