<?php

namespace App\Filament\Resources\UsersEmployeeResource\Pages;

use App\Filament\Resources\UsersEmployeeResource;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUsersEmployee extends CreateRecord
{
    protected static string $resource = UsersEmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['start_date'] = Carbon::now();
        return $data;
    }

    protected function beforeCreate(): void
    {
        $userId = $this->form->getState()['user_id'] ?? null;
        // dd($userId);
        if ($userId) {
            $user = User::find($userId);
            if ($user && !$user->is_available) {
                Notification::make()
                ->warning()
                ->title('Usuario ya asignado a otro empleado')
                ->body('Consulte sus registros')
                ->persistent()
                ->send();
        
                $this->halt();
            }
        }
    }

    protected function afterCreate(): void
    {
        $userId = $this->record->user_id ?? null;
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $user->is_available = false;
                $user->save();
            }
        }
    }
}
