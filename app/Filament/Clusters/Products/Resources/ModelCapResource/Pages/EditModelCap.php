<?php

namespace App\Filament\Clusters\Products\Resources\ModelCapResource\Pages;

use App\Filament\Clusters\Products\Resources\ModelCapResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModelCap extends EditRecord
{
    protected static string $resource = ModelCapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            // Actions\DeleteAction::make(),
            // Actions\ForceDeleteAction::make(),
            // Actions\RestoreAction::make(),
        ];
    }
}
