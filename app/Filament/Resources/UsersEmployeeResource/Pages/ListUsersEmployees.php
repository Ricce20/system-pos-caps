<?php

namespace App\Filament\Resources\UsersEmployeeResource\Pages;

use App\Filament\Resources\UsersEmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsersEmployees extends ListRecords
{
    protected static string $resource = UsersEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
