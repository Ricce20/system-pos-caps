<?php

namespace App\Filament\Clusters\Products\Resources\ModelCapResource\Pages;

use App\Filament\Clusters\Products\Resources\ModelCapResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewModelCap extends ViewRecord
{
    protected static string $resource = ModelCapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
