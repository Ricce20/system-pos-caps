<?php

namespace App\Filament\Resources\UsersEmployeeResource\Pages;

use App\Filament\Resources\UsersEmployeeResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
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

    public function getTabs(): array
    {
        return [
            Tab::make('Activos')
                ->modifyQueryUsing(fn ($query) => $query->where('active', true))
                ->badge(fn () => \App\Models\UsersEmployee::where('active', true)->count()),
            Tab::make('Historial')
                ->modifyQueryUsing(fn ($query) => $query->where('active', false))
                ->badge(fn () => \App\Models\UsersEmployee::where('active', false)->count()),
        ];
    }
}
