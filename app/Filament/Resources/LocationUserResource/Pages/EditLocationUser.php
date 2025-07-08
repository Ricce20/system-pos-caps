<?php

namespace App\Filament\Resources\LocationUserResource\Pages;

use App\Filament\Resources\LocationUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLocationUser extends EditRecord
{
    protected static string $resource = LocationUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
