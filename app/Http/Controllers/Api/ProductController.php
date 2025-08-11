<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\SupplierItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function findByCode(string $code)
    {
        $mobileWarehouseId = 1;

        Log::info("API findByCode: code='{$code}'");

        // Busca el item disponible, cargando relaciones necesarias
        $item = Item::with(['product', 'size'])
            ->whereRaw('TRIM(code) = ?', [trim($code)])
            ->where('is_available', true)
            ->first();

        if (!$item) {
            $debug = Item::whereRaw('TRIM(code) = ?', [trim($code)])->first();
            if ($debug) {
                Log::warning("Item hallado pero NO disponible. ID={$debug->id}");
            } else {
                Log::warning("Item no encontrado por code={$code}");
            }
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado o no disponible.',
            ], 404);
        }

        // Precio de venta (primario) con fallback
        $salePrice = SupplierItem::where('item_id', $item->id)
            ->where('is_primary', true)
            ->value('sale_price');

        if ($salePrice === null) {
            // Si no hay supplier primario, usa la primera fila o 0
            $salePrice = SupplierItem::where('item_id', $item->id)->value('sale_price') ?? 0;
        }

        // Stock por almacén móvil (2) y total (suma de todos los almacenes)
        $stockAlmacen = (int) (DB::table('warehouse_items')
            ->where('item_id', $item->id)
            ->where('warehouse_id', $mobileWarehouseId)
            ->value('stock') ?? 0);

        $stockTotal = (int) DB::table('warehouse_items')
            ->where('item_id', $item->id)
            ->sum('stock');

        Log::info("Item ID={$item->id} stock_almacen={$stockAlmacen} stock_total={$stockTotal}");

        return response()->json([
            'success' => true,
            'data' => [
                'id'             => $item->id,
                'name'           => trim(($item->product->name ?? '') . ' - ' . ($item->size->name ?? '')),
                'price'          => (string) $salePrice,   // Android espera string
                'code'           => $item->code,
                // compat: lo que ya consumía tu app
                'stock'          => $stockAlmacen,
                // nuevos campos para que puedas decidir en la app
                'stock_almacen'  => $stockAlmacen,
                'stock_total'    => $stockTotal,
            ],
        ], 200);
    }
}
