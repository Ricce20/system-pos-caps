<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class SalesStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 2;
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $nextMonth = Carbon::now()->addMonth()->startOfMonth();

        return [
            Stat::make('Ventas del Día', Sale::whereDate('sale_date', $today)->count())
                ->description('Total de ventas realizadas hoy')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success'),

            Stat::make('Ventas del Mes', Sale::whereBetween('sale_date', [$thisMonth, $nextMonth])->count())
                ->description('Total de ventas del mes actual')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('Ventas del Mes Anterior', Sale::whereBetween('sale_date', [$lastMonth, $thisMonth])->count())
                ->description('Total de ventas del mes anterior')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),

            Stat::make('Total de Ventas', Sale::count())
                ->description('Total histórico de ventas')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),
        ];
    }
} 