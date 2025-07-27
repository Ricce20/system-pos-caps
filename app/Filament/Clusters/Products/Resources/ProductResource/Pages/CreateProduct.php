<?php

namespace App\Filament\Clusters\Products\Resources\ProductResource\Pages;

use App\Filament\Clusters\Products\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Guardar'),
            $this->getCreateAnotherFormAction()->label('Guardar y Registrar Otro'),
            $this->getCancelFormAction()->label('Cancelar')
        ];
    }

    public function getTitle(): string
    {
        return "Crear Producto";
    }
}
