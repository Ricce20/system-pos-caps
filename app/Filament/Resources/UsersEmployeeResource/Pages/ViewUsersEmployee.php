<?php

namespace App\Filament\Resources\UsersEmployeeResource\Pages;

use App\Filament\Resources\UsersEmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUsersEmployee extends ViewRecord
{
    protected static string $resource = UsersEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
