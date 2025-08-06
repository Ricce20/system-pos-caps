<?php

namespace App\Filament\Empleado\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemRelationManager extends RelationManager
{
    protected static string $relationship = 'Item';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('product_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
        ->heading('Producto por tallas')
        ->recordTitleAttribute('product_id')
        ->columns([
            Tables\Columns\TextColumn::make('size.name')
                ->label('Talla')
                ->sortable(),

            Tables\Columns\TextColumn::make('size.measurement')
                ->label('Medida')
                ->sortable(),

            Tables\Columns\TextColumn::make('code')
                ->label('Código')
                ->searchable()
                ->sortable(),

            Tables\Columns\ImageColumn::make('barcode')
                ->disk('public')
                ->visibility('public')
                ->label('Código de barras'),

            Tables\Columns\IconColumn::make('is_available')
                ->label('Disponible')
                ->boolean()
        ])
        ->filters([
            Tables\Filters\TrashedFilter::make()
                ->native(false),
            Tables\Filters\SelectFilter::make('activos')
                ->options([
                    true => 'Disponibles',
                    false => 'No Disponibles'
                ])->attribute('is_available')
                ->native(false)
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
        ])
        ->modifyQueryUsing(fn (Builder $query) => 
            $query->latest() // Equivale a ->orderBy('created_at', 'desc')
        )
        ->deferLoading();
    }
}
