<?php

namespace App\Filament\Resources\UsersEmployeeResource\Pages;

use App\Filament\Resources\UsersEmployeeResource;
use App\Models\User;
use App\Models\UsersEmployee;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUsersEmployee extends EditRecord
{
    protected static string $resource = UsersEmployeeResource::class;

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
