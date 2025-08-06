<?php

namespace App\Filament\Clusters\Suppliers\Resources\EntryOrderResource\RelationManagers;

use App\Models\EntryOrderDetail;
use App\Models\SupplierItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EntryOrderDetailRelationManager extends RelationManager
{
    protected static string $relationship = 'EntryOrderDetail';

    protected static ?string $title = 'Detalles de Compra';


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('item_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item_id')
            ->columns([
                Tables\Columns\TextColumn::make('item.product.name')
                    ->description(fn (EntryOrderDetail $record):string => "{$record->item->size->name}")
                    ->label('Artículo')
                    ->wrap(),
                Tables\Columns\TextColumn::make('item.size.name')
                    ->label('Talla'),
                    
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->label('Cantidad')
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('sale_price')
                    ->label('Precio de compra')
                    ->getStateUsing(function (EntryOrderDetail $record): ?float {
                        return SupplierItem::where('item_id', $record->item_id)
                            ->where('is_primary', true)
                            ->value('purchase_price');
                    })
                    ->money('MXN')
                    ->prefix('$')
                    ->suffix('MXN')
                    ->numeric()
                    ->wrap(),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->numeric()
                    ->prefix('$')
                    ->suffix('MXN')
                    ->wrap(),
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
