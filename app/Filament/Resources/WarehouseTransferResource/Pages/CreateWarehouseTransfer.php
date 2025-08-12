<?php

namespace App\Filament\Resources\WarehouseTransferResource\Pages;

use App\Filament\Resources\WarehouseTransferResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateWarehouseTransfer extends CreateRecord
{
    protected static string $resource = WarehouseTransferResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->user()->id;
        $data['status'] = 'pendiente';
        $data['approved_at'] = Carbon::now();
        return $data;
    }

    public function beforeCreate(): void
    {
        $data = $this->form->getState();
        if ($data['source_warehouse_id'] == $data['destination_warehouse_id']) {
            Notification::make()
                ->warning()
                ->title('El almacén de destino no puede ser el mismo que el de origen.')
                ->persistent()
                ->send();
        
            $this->halt();
        }
    }

    public function handleRecordCreation(array $data): Model
    {
        $warehouseTransfer = static::getModel()::create($data);

        // Crear los detalles de la transferencia y ajustar el stock
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                // Crear el detalle de la transferencia
                $warehouseTransfer->warehouseTransferDetail()->create([
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                ]);

                // Descontar stock del almacén origen
                // $sourceWarehouseItem = \App\Models\WarehouseItem::where('warehouse_id', $data['source_warehouse_id'])
                //     ->where('item_id', $item['item_id'])
                //     ->first();

                // if ($sourceWarehouseItem) {
                //     $sourceWarehouseItem->stock -= $item['quantity'];
                //     if ($sourceWarehouseItem->stock < 0) {
                //         $sourceWarehouseItem->stock = 0;
                //     }
                //     $sourceWarehouseItem->save();
                // }

                // // Sumar stock al almacén destino
                // $destinationWarehouseItem = \App\Models\WarehouseItem::firstOrCreate(
                //     [
                //         'warehouse_id' => $data['destination_warehouse_id'],
                //         'item_id' => $item['item_id'],
                //     ],
                //     [
                //         'stock' => 0,
                //     ]
                // );
                // $destinationWarehouseItem->stock += $item['quantity'];
                // $destinationWarehouseItem->save();
            }
        }

        return $warehouseTransfer;
    }
}
