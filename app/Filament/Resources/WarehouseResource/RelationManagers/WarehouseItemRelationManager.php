<?php

namespace App\Filament\Resources\WarehouseResource\RelationManagers;

use App\Models\Item;
use App\Models\Warehouse;
use App\Models\WarehouseItem;
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
                    ->label('Producto')
                    // ->relationship(
                    //     'item','product_id'
                    // )
                    ->options(function(){
                        $itemsId = WarehouseItem::where('warehouse_id',$this->getOwnerRecord()->id)->pluck('item_id');

                        return Item::with(['product','size'])->whereNotIn('id',$itemsId)->where('is_available',true)->get()
                        ->mapWithKeys(fn ($item) => [
                            $item->id => "{$item->product->name} - {$item->size->name}"
                        ]);;
                    })
                    // ->getOptionLabelFromRecordUsing(fn (Model $record) => "{$record->product->name} - {$record->size->name}")
                    ->preload()
                    ->searchable()
                    ->native(false)
                    ->required()
                    ->unique(modifyRuleUsing: function(Unique $rule, Get $get){
                        return $rule->where('warehouse_id',$this->getOwnerRecord()->getKey())->where('item_id',$get('item_id'));
                    }),
                Forms\Components\TextInput::make('stock')
                    ->label('Stock(Cantidad)')
                    ->required()
                    ->numeric()
                    ->placeholder('ej: 100')
                    ->minValue(1),
                Forms\Components\Toggle::make('is_available')
                    ->label('Disponible')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-circle')
                    ->required()
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Productos en Almacen')
            ->recordTitleAttribute('warehouse_id')
            ->columns([
                Tables\Columns\TextColumn::make('item.product.name')
                    ->label('Producto'),
                Tables\Columns\TextColumn::make('item.size.name')
                    ->label('Talla'),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock'),
                Tables\Columns\ToggleColumn::make('is_available')
                    ->label('Disponible')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-circle'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar producto'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->before(function (WarehouseItem $record) {
                            // dd($record);
                            $record->update(['is_available' => false]);
                        }),
                    Tables\Actions\RestoreAction::make()
                        ->after(function (WarehouseItem $record) {
                            $record->update(['is_available' => true]);
                        })
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
            ->deferLoading()->modifyQueryUsing(fn (Builder $query) => 
                $query->latest() // Equivale a ->orderBy('created_at', 'desc')
            )
            ->deferLoading();
    }
}
