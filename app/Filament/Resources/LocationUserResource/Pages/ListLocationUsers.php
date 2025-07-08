<?php

namespace App\Filament\Resources\LocationUserResource\Pages;

use App\Filament\Resources\LocationUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLocationUsers extends ListRecords
{
    protected static string $resource = LocationUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
