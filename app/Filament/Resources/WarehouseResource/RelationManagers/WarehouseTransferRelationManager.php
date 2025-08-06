<?php

namespace App\Filament\Resources\WarehouseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\WarehouseItem;
use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;

class TransfersAsSourceRelationManager extends RelationManager
{
    protected static string $relationship = 'transfersAsSource';

    public function form(Form $form): Form
    {
        $warehouseId = $this->getOwnerRecord()->id;
        $itemsWithStock = WarehouseItem::where('warehouse_id', $warehouseId)
            ->where('stock', '>', 0)
            ->with('item')
            ->get();
        $itemOptions = $itemsWithStock->mapWithKeys(function($wi) {
            return [$wi->item_id => $wi->item->code . ' (Stock: ' . $wi->stock . ')'];
        })->toArray();

        $otherWarehouses = Warehouse::where('id', '!=', $warehouseId)->pluck('name', 'id');

        return $form
            ->schema([
                Forms\Components\Select::make('item_id')
                    ->label('Producto')
                    ->options($itemOptions)
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) use ($warehouseId) {
                        $stock = WarehouseItem::where('warehouse_id', $warehouseId)
                            ->where('item_id', $state)
                            ->value('stock') ?? 0;
                        $set('max_quantity', $stock);
                    }),
                Forms\Components\TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(fn ($get) => $get('max_quantity') ?? 1)
                    ->required()
                    ->helperText(fn ($get) => 'Stock disponible: ' . ($get('max_quantity') ?? '')),
                Forms\Components\Hidden::make('max_quantity'),
                Forms\Components\Select::make('destination_warehouse_id')
                    ->label('Destino')
                    ->options($otherWarehouses)
                    ->required(),
                Forms\Components\Textarea::make('notes'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('transfer_number')
            ->columns([
                Tables\Columns\TextColumn::make('transfer_number')->label('N° Transferencia'),
                Tables\Columns\TextColumn::make('item.product.name')->label('Producto'),
                Tables\Columns\TextColumn::make('item.size.name')->label('Talla'),
                Tables\Columns\TextColumn::make('quantity')->label('Cantidad'),
                // Tables\Columns\TextColumn::make('stock_disponible')->label('Stock Disponible')->getStateUsing(function($record) {
                //     $warehouseId = $record->source_warehouse_id;
                //     $itemId = $record->item_id;
                //     return WarehouseItem::where('warehouse_id', $warehouseId)
                //         ->where('item_id', $itemId)
                //         ->value('stock') ?? 0;
                // }),
                Tables\Columns\TextColumn::make('destinationWarehouse.name')->label('Destino'),
                Tables\Columns\TextColumn::make('status_label')->label('Estado')->badge()->color(fn($record) => $record->status_color),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data, RelationManager $livewire) {
                        $warehouseId = $livewire->getOwnerRecord()->id;
                        $userId = Auth::id();
                        return $livewire->getRelationship()->create([
                            'item_id' => $data['item_id'],
                            'quantity' => $data['quantity'],
                            'destination_warehouse_id' => $data['destination_warehouse_id'],
                            'source_warehouse_id' => $warehouseId,
                            'notes' => $data['notes'] ?? null,
                            'created_by' => $userId,
                            'status' => 'pending',
                        ]);
                    }),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn($record) => $record->isPending())
                    ->action(function($record) {
                        $user = Auth::user();
                        if ($record->approve($user)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Transferencia aprobada')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('No se pudo aprobar')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('completar')
                    ->label('Completar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn($record) => $record->isInTransit())
                    ->action(function($record) {
                        if ($record->complete()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Transferencia completada y stock actualizado')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('No se pudo completar. Stock insuficiente.')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->isPending() || $record->isInTransit())
                    ->requiresConfirmation()
                    ->action(function($record) {
                        if ($record->cancel()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Transferencia cancelada')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('No se pudo cancelar')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

class TransfersAsDestinationRelationManager extends RelationManager
{
    protected static string $relationship = 'transfersAsDestination';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('item_id')->required(),
                Forms\Components\TextInput::make('quantity')->required(),
                Forms\Components\TextInput::make('source_warehouse_id')->required(),
                Forms\Components\Textarea::make('notes'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('transfer_number')
            ->columns([
                Tables\Columns\TextColumn::make('transfer_number')->label('N° Transferencia'),
                Tables\Columns\TextColumn::make('item.code')->label('Producto'),
                Tables\Columns\TextColumn::make('quantity')->label('Cantidad'),
                Tables\Columns\TextColumn::make('sourceWarehouse.name')->label('Origen'),
                Tables\Columns\TextColumn::make('status_label')->label('Estado')->badge()->color(fn($record) => $record->status_color),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
            ])
            ->filters([])
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
