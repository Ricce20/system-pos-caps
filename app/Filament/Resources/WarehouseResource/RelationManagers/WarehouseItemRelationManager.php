<?php

namespace App\Filament\Resources\WarehouseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;

class WarehouseItemRelationManager extends RelationManager
{
    protected static string $relationship = 'WarehouseItems';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('item_id')
                    ->relationship(
                        'item','product_id'
                    )
                    ->getOptionLabelFromRecordUsing(fn (Model $record) => "{$record->product->name} - {$record->size->name}")
                    ->preload()
                    ->native(false)
                    ->required()
                    ->unique(modifyRuleUsing: function(Unique $rule, Get $get){
                        return $rule->where('warehouse_id',$this->getOwnerRecord()->getKey())->where('item_id',$get('item_id'));
                    }),
                Forms\Components\TextInput::make('stock')
                    ->required()
                    ->numeric(),
                Forms\Components\Toggle::make('is_available')
                    ->label('Disponible')
                    ->required()
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('warehouse_id')
            ->columns([
                Tables\Columns\TextColumn::make('item.product.name')
                    ->label('Producto'),
                Tables\Columns\TextColumn::make('item.size.name')
                    ->label('Talla'),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock'),
                Tables\Columns\IconColumn::make('is_available')
                    ->label('Disponible')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
