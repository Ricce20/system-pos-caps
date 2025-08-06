<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use App\Models\SaleDetail;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LeastSoldProductsTable extends BaseWidget
{
    protected static ?string $heading = 'Top 10 Productos Menos Vendidos';
    protected static ?int $sort = 6;
    protected int|string|array $columnSpan = 2;
    protected static ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        $query = Item::query()
            ->with(['product.brand', 'product.category','size'])
            ->select('items.*')

            // Subconsulta: total de unidades vendidas
            ->selectSub(
                SaleDetail::selectRaw('COALESCE(SUM(quantity),0)')
                    ->whereColumn('item_id', 'items.id'),
                'total_sold'
            )

            // Subconsulta: ingresos totales
            ->selectSub(
                SaleDetail::selectRaw('COALESCE(SUM(subtotal),0)')
                    ->whereColumn('item_id', 'items.id'),
                'total_revenue'
            )

            // Solo productos disponibles
            ->whereHas('product', fn($q) => $q->where('is_available', true))

            // Menos vendidos primero
            ->orderBy('total_sold', 'asc')

            // Solo los 10 primeros
            ->limit(10);

        return $table
            ->query($query)
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

                Tables\Columns\TextColumn::make('product.category.name')
                    ->label('Categoría')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_sold')
                    ->label('Cantidad Vendida')
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Ingresos Totales')
                    ->money('MXN')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}
