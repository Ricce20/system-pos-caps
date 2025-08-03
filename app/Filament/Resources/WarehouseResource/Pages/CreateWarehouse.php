<?php

namespace App\Filament\Resources\WarehouseResource\Pages;

use App\Filament\Resources\WarehouseResource;
use App\Models\Warehouse;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWarehouse extends CreateRecord
{
    protected static string $resource = WarehouseResource::class;


    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        if(Warehouse::where('is_primary',true)->exists() && $data['is_primary'] === true){
            Warehouse::where('is_primary',true)
                ->update(['is_primary' => false]);
                return;
        }
        return;

    }

}
