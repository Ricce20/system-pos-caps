<?php

namespace App\Filament\Resources\WarehouseResource\Pages;

use App\Filament\Resources\WarehouseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWarehouse extends EditRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $recordId = $this->getRecord()?->id; // null si es create

        // Si el toggle is_primary está activo
        if (!empty($data['is_primary']) && $data['is_primary'] === true) {
            // Desmarcar todos los demás
            \App\Models\Warehouse::where('id', '!=', $recordId)
                ->update(['is_primary' => false]);
        }

        return $data;
    }

    protected function beforeCreate(): void
    {
        // Runs before the form fields are saved to the database.
    }

}
