<?php

namespace App\Filament\Clusters\Products\Resources\BrandResource\Pages;

use App\Filament\Clusters\Products\Resources\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBrand extends EditRecord
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Ver'),
            Actions\DeleteAction::make()
                ->label('Eliminar'),
            Actions\ForceDeleteAction::make()
                ->label('Forzar Eliminacion'),
            Actions\RestoreAction::make()
                ->label('Restaurar'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Guardar'),
            $this->getCancelFormAction()
                ->label('Cancelar'),
        ];
    }


    public function getTitle(): string
    {
        return "Editar Marcar";
    }
}
