<?php

namespace App\Filament\Empleado\Resources\ProductResource\Pages;

use App\Filament\Empleado\Resources\ProductResource;
use Filament\Actions;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos'),
            'active' => Tab::make('Disponibles')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_available', true)),
            'inactive' => Tab::make('No Disponibles')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_available', false)),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'active';
    }
}
