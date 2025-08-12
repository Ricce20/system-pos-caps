<?php

namespace App\Filament\Clusters\Products\Resources\ProductResource\Pages;

use App\Filament\Clusters\Products\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            // Actions\DeleteAction::make(),
            // Actions\ForceDeleteAction::make(),
            // Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 1️⃣ Obtener las imágenes anteriores del registro actual
        $product = $this->getRecord()->only(['image_1', 'image_2', 'image_3']);

        $imagenes_anteriores = [
            $product['image_1'] ?? null,
            $product['image_2'] ?? null,
            $product['image_3'] ?? null,
        ];

        // 2️⃣ Imágenes nuevas que vienen del formulario
        $imagenes_nuevas = [
            $data['image_1'] ?? null,
            $data['image_2'] ?? null,
            $data['image_3'] ?? null,
        ];

        // 3️⃣ Recorrer y eliminar solo si el nombre cambió
        foreach ($imagenes_anteriores as $index => $imagen_antigua) {
            $imagen_nueva = $imagenes_nuevas[$index] ?? null;

            // Si había imagen y cambió
            if ($imagen_antigua && $imagen_antigua !== $imagen_nueva) {
                if (Storage::disk('public')->exists($imagen_antigua)) {
                    Storage::disk('public')->delete($imagen_antigua);
                }
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        if(!$record->is_available){
            $record->item()->update(['is_available' => false]);
            return;
        }else{
            $record->item()->update(['is_available' => true]);
            return;
        }
        return;
    }


}
