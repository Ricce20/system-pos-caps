<?php

namespace App\Filament\Clusters\Suppliers\Resources\SupplierResource\Pages;

use App\Filament\Clusters\Suppliers\Resources\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            // Actions\DeleteAction::make(),
            // Actions\ForceDeleteAction::make(),
            // Actions\RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        if(!$record->is_available){
            $record->supplierItems()->update(['is_available' => false]);
            return;
        }else{
            $record->supplierItems()->update(['is_available' => true]);
            return;
        }
        return;
    }
}
