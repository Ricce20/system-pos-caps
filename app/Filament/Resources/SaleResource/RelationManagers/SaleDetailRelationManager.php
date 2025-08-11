<?php

namespace App\Filament\Resources\SaleResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SaleDetailRelationManager extends RelationManager
{
    // 👈 Debe coincidir con el método del modelo Sale: saleDetails()
    protected static string $relationship = 'saleDetails';

    protected static ?string $title = 'Detalles de la venta';

    // Lo dejamos vacío: relación de solo lectura
    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item.code')
                    ->label('Código')
                    ->searchable(),

                Tables\Columns\TextColumn::make('item.product.name')
                    ->label('Producto')
                    ->searchable(),

                Tables\Columns\TextColumn::make('item.size.name')
                    ->label('Talla')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->sortable(),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('mxn')      // formatea como moneda
                    ->sortable(),
            ])
            // Sin crear/editar/borrar desde aquí (solo lectura)
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
