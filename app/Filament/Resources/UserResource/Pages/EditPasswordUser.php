<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Form;

class EditPasswordUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $navigationLabel = 'Cambiar Contraseña';

    public static function getResourceForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('password')
                ->label('Nueva contraseña')
                ->password()
                ->required()
                ->minLength(8)
                ->maxLength(255)
                ->confirmed(),
            Forms\Components\TextInput::make('password_confirmation')
                ->label('Confirmar contraseña')
                ->password()
                ->required(),
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['password'] = bcrypt($data['password']);
        unset($data['password_confirmation']);
        return $data;
    }
}
