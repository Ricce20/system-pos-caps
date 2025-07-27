<?php

namespace App\Filament\Clusters\Products\Resources\ModelCapResource\Pages;

use App\Filament\Clusters\Products\Resources\ModelCapResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModelCaps extends ListRecords
{
    protected static string $resource = ModelCapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
