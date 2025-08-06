<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use App\Models\SaleDetail;
use App\Models\SupplierItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopProductsTable extends BaseWidget
{
    protected static ?string $heading = 'Top 10 Productos Más Vendidos';

    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 2;
    protected static ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Item::query()
                    ->with(['product.brand', 'product.category','size'])
                    ->select('items.*')
                    ->selectSub(
                        SaleDetail::selectRaw('COALESCE(SUM(quantity),0)')
                            ->whereColumn('item_id', 'items.id'),
                        'total_sold'
                    )
                    ->selectSub(
                        SaleDetail::selectRaw('COALESCE(SUM(subtotal),0)')
                            ->whereColumn('item_id', 'items.id'),
                        'total_revenue'
                    )
                    ->selectSub(
                        SupplierItem::select('sale_price')
                            ->whereColumn('item_id', 'items.id')
                            ->where('is_primary', true)
                            ->limit(1),
                        'sale_price'
                    )
                    ->whereHas('product', fn($q) => $q->where('is_available', true))
                    ->orderByDesc('total_sold')
                    ->limit(10)            
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('size.name')
                    ->label('Talla'),
                Tables\Columns\TextColumn::make('product.brand.name')
                    ->label('Marca')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_price')
                    ->label('Precio de Venta')
                    ->money('MXN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_sold')
                    ->label('Cantidad Vendida')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Ingresos Totales')
                    ->money('MXN')
                    ->sortable(),
                
            ])
            ->paginated(false);
    }
}
