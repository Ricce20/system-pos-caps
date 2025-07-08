<?php

namespace App\Filament\Resources\LocationUserResource\Pages;

use App\Filament\Resources\LocationUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLocationUser extends ViewRecord
{
    protected static string $resource = LocationUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
