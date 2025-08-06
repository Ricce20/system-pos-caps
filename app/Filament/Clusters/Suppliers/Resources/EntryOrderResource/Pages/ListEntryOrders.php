<?php

namespace App\Filament\Clusters\Suppliers\Resources\EntryOrderResource\Pages;

use App\Filament\Clusters\Suppliers\Resources\EntryOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEntryOrders extends ListRecords
{
    protected static string $resource = EntryOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
        ];
    }
}
