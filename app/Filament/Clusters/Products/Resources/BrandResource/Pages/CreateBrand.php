<?php

namespace App\Filament\Clusters\Products\Resources\BrandResource\Pages;

use App\Filament\Clusters\Products\Resources\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;

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
        return "Nueva Marca";
    }
}
