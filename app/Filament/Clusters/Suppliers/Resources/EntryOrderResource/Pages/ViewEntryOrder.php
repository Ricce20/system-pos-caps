<?php

namespace App\Filament\Clusters\Suppliers\Resources\EntryOrderResource\Pages;

use App\Filament\Clusters\Suppliers\Resources\EntryOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEntryOrder extends ViewRecord
{
    protected static string $resource = EntryOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
