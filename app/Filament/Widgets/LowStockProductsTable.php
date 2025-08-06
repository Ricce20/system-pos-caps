<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Item;
use App\Models\WarehouseItem;
use App\Models\Warehouse;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class LowStockProductsTable extends BaseWidget
{
    protected static ?string $heading = 'Productos con Bajo Stock';

    protected static ?int $sort = 7;
    protected int | string | array $columnSpan = 2;
    protected static ?string $pollingInterval = null;


    public function table(Table $table): Table
    {
        return $table
            ->query(
                WarehouseItem::query()
                    ->with(['item.product.brand', 'item.product.category', 'warehouse','item.size'])
                    ->where('is_available', true)
                    ->where('stock', '<=', 40) // Solo mostrar productos con bajo stock
            )
            ->columns([
                Tables\Columns\TextColumn::make('item.product.name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.size.name')
                    ->label('Talla'),
                Tables\Columns\TextColumn::make('item.product.brand.name')
                    ->label('Marca')
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.product.category.name')
                    ->label('Categoría')
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Almacén')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->badge()
                    ->color(function (int $state): string {
                        if ($state <= 10) {
                            return 'danger';
                        } elseif ($state <= 40) {
                            return 'warning';
                        } else {
                            return 'success';
                        }
                    }),
                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Estado')
                    ->getStateUsing(function (WarehouseItem $record): string {
                        if ($record->stock <= 10) {
                            return 'Crítico';
                        } elseif ($record->stock <= 40) {
                            return 'Bajo';
                        } else {
                            return 'Normal';
                        }
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'Crítico' => 'danger',
                            'Bajo' => 'warning',
                            default => 'success',
                        };
                    }),
            ])
            ->defaultSort('stock', 'asc')
            ->paginated(false);
    }
} 