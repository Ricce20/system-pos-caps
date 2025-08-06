<?php

namespace App\Filament\Resources\CashRegisterResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CashRegisterDetailRelationManager extends RelationManager
{
    protected static string $relationship = 'CashRegisterDetail';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('start_date')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Cortes de caja')
            // ->recordTitleAttribute('cash_register_id')
            ->columns([
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha de apertura')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha de cierre')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('starting_quantity')
                    ->numeric()
                    ->label('Cantidad de inicio')
                    ->description('Apertura')
                    ->prefix('$')
                    ->suffix('MXN'),
                Tables\Columns\TextColumn::make('closing_amount')
                    ->label('Contado por el sistema')
                    ->description('Cierre')
                    ->numeric()
                    ->prefix('$')
                    ->suffix('MXN'),

                Tables\Columns\TextColumn::make('counted_amount')
                    ->label('Contado por el usuario')
                    ->description('Cierre')
                    ->numeric()
                    ->prefix('$')
                    ->suffix('MXN'),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Tables\Actions\ViewAction::make(),
                    // Tables\Actions\EditAction::make(),
                ])
                ->button()
                ->label('Acciones')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => 
                $query->latest() // Equivale a ->orderBy('created_at', 'desc')
            )
            ->deferLoading();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
