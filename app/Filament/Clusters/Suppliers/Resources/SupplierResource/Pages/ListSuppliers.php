<?php

namespace App\Filament\Clusters\Suppliers\Resources\SupplierResource\Pages;

use App\Filament\Clusters\Suppliers\Resources\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
