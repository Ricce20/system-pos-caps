<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'today' => Tab::make('Ventas de Hoy')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('sale_date', Carbon::today()))
                ->badge(fn () => \App\Models\Sale::whereDate('sale_date', Carbon::today())->count())
                ->badgeColor('success'),
            'all' => Tab::make('Todas las Ventas')
                ->modifyQueryUsing(fn (Builder $query) => $query)
                ->badge(fn () => \App\Models\Sale::count())
                ->badgeColor('gray'),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return 'today';
    }
}
