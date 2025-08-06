<?php

namespace App\Filament\Widgets;

use App\Models\Location;
use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class LocationSalesChart extends ChartWidget
{
    protected static ?string $pollingInterval = null;
    protected static ?string $heading = 'Ventas mensuales por sucursal';
    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        $start = now()->startOfYear();
        $end = now()->endOfYear();

        // Obtener todas las sucursales activas
        $stores = Location::where('active', true)->get(['id', 'name']);

        $datasets = [];
        $labels = [];

        foreach ($stores as $store) {
            // Obtener tendencia mensual por sucursal
            $data = Trend::query(
                Sale::where('location_id', $store->id)
            )
            ->dateColumn('sale_date')
            ->between(start: $start, end: $end)
            ->perMonth()
            ->count();

            // Inicializar los labels solo la primera vez
            if (empty($labels)) {
                $labels = $data->map(fn (TrendValue $value) => 
                    Carbon::parse($value->date)->translatedFormat('F') // Nombre del mes
                )->toArray();
            }

            $datasets[] = [
                'label' => $store->name,
                'data' => $data->map(fn (TrendValue $value) => $value->aggregate)->toArray(),
                'borderColor' => $this->randomColor(),
                'backgroundColor' => 'transparent',
                'fill' => false,
                'tension' => 0.3,
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    public function getDescription(): ?string
    {
        return 'Ventas mensuales del año actual separadas por sucursal.';
    }

    protected function getType(): string
    {
        return 'line';
    }

    private function randomColor(): string
    {
        $colors = ['#E57373','#64B5F6','#81C784','#FFD54F','#BA68C8','#4DB6AC','#F06292'];
        return $colors[array_rand($colors)];
    }
}
