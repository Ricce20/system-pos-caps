<?php

namespace App\Filament\Clusters\Suppliers\Resources\EntryOrderResource\Pages;

use App\Filament\Clusters\Suppliers\Resources\EntryOrderResource;
use App\Models\EntryOrder;
use App\Models\Warehouse;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateEntryOrder extends CreateRecord
{
    protected static string $resource = EntryOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Si falta la fecha de la orden, asignar la fecha actual
        if (!isset($data['fecha_orden'])) {
            $data['fecha_orden'] = now()->format('Y-m-d H:i:s');
        }

        // Si faltan notas, ponerlas como nulas
        if (!isset($data['notes'])) {
            $data['notes'] = null;
        }
        
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
{
    // Guardar la orden de entrada
    $entryOrder = static::getModel()::create([
        'supplier_id' => $data['supplier_id'],
        'user_id' => auth()->user()->id,
        'date_order' => $data['fecha_orden'] ?? now()->format('Y-m-d H:i:s'),
        'notes' => $data['notes'] ?? null,
        'total' => $data['total'] ?? 0,
    ]);

    $warehouse = Warehouse::where('is_primary', true)->first();

    // Guardar los detalles y actualizar stock
    if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                // Guardar detalle
                $entryOrder->entryOrderDetail()->create([
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Actualizar stock en item_warehouse
                $entry = DB::table('warehouse_items')->where([
                    'item_id' => $item['item_id'],
                    'warehouse_id' => $warehouse->id,
                ])->first();

                if ($entry) {
                    // Sumar cantidad
                    DB::table('warehouse_items')
                        ->where('id', $entry->id)
                        ->update([
                            'stock' => $entry->stock + $item['quantity'],
                            'updated_at' => now(),
                        ]);
                } else {
                    // Insertar nuevo registro
                    DB::table('warehouse_items')->insert([
                        'item_id' => $item['item_id'],
                        'warehouse_id' => $warehouse->id,
                        'stock' => $item['quantity'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        return $entryOrder;
    }   

}
