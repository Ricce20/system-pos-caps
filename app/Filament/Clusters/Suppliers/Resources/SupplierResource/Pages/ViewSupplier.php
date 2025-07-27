<?php

namespace App\Filament\Clusters\Suppliers\Resources\SupplierResource\Pages;

use App\Filament\Clusters\Suppliers\Resources\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplier extends ViewRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
