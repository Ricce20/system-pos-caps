<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\CashRegister;

class SaleController extends Controller
{
    /**
     * Crear venta desde la app móvil.
     * - Usuario fijo: Administrador (id=1)
     * - Descuenta stock del almacén 1
     * - Transaccional
     */
    public function create(Request $request)
    {
        $data = $request->validate([
            'total'              => 'required|numeric|min:0',
            'method_of_payment'  => 'required|in:Efectivo,Tarjeta',
            'cash_register_id'   => 'required|integer|exists:cash_registers,id',
            'cart'               => 'required|array|min:1',
            'cart.*.id'          => 'required|integer|exists:items,id',
            'cart.*.quantity'    => 'required|integer|min:1',
            'cart.*.price'       => 'required|numeric|min:0',
        ]);

        $adminId     = 1; // Usuario Administrador
        $warehouseId = 1; // Almacén principal

        try {
            $saleId = DB::transaction(function () use ($data, $adminId, $warehouseId) {
                $cashRegister = CashRegister::findOrFail($data['cash_register_id']);

                // Encabezado de venta
                $sale = Sale::create([
                    'sale_date'         => now('America/Mexico_City'),
                    'total'             => $data['total'],
                    'location_id'       => $cashRegister->location_id,
                    'cash_register_id'  => $data['cash_register_id'],
                    'method_of_payment' => $data['method_of_payment'],
                    'is_check'          => false,
                    'user_id'           => $adminId,
                    //'employee_id'       => '',
                ]);

                // Validar/descontar stock y preparar detalles
                $details = [];
                foreach ($data['cart'] as $item) {
                    $itemId = (int) $item['id'];
                    $qty    = (int) $item['quantity'];
                    $price  = (float) $item['price'];

                    // Descontar solo si hay stock suficiente en almacén 1
                    $affected = DB::table('warehouse_items')
                        ->where('warehouse_id', $warehouseId)
                        ->where('item_id', $itemId)
                        ->where('stock', '>=', $qty)
                        ->decrement('stock', $qty);

                    if ($affected === 0) {
                        throw new \RuntimeException("Stock insuficiente para item {$itemId} en almacén {$warehouseId}.");
                    }

                    $details[] = [
                        'sale_id'  => $sale->id,
                        'item_id'  => $itemId,
                        'quantity' => $qty,
                        'subtotal' => $price * $qty,
                    ];
                }

                if (!empty($details)) {
                    SaleDetail::insert($details);
                }

                return $sale->id;
            });

            return response()->json([
                'success' => true,
                'message' => 'Venta registrada con éxito.',
                'sale_id' => $saleId,
            ], 201);

        } catch (\RuntimeException $e) {
            // Error de negocio (stock insuficiente, etc.)
            Log::warning('Venta rechazada por negocio', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar la venta.',
                'error'   => $e->getMessage(),
            ], 422);

        } catch (\Throwable $e) {
            // Error inesperado
            Log::error('Error al registrar venta desde app', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la venta.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listado de ventas con filtros (sin migraciones):
     * - period: "day" | "week" | "month"
     * - q: texto (nombre de producto o código)
     * - cash_register_id: filtra por caja (opcional)
     * - location_id: filtra por ubicación (opcional)
     * - page/per_page: paginación
     *
     * Ejemplos:
     *   GET /api/sales?period=day
     *   GET /api/sales?location_id=1&period=month&q=cena
     *   GET /api/sales?cash_register_id=2&page=2
     */
    public function index(Request $request)
    {
        $period       = $request->input('period');             // "day"|"week"|"month"
        $q            = trim((string) $request->input('q', ''));
        $perPage      = (int) $request->input('per_page', 20);
        $cashRegister = $request->input('cash_register_id');   // opcional
        $locationId   = $request->input('location_id');        // opcional

        // Rango de fechas por period
        $now  = now('America/Mexico_City');
        $from = $to = null;
        if ($period === 'day')   { $from = $now->copy()->startOfDay();   $to = $now->copy()->endOfDay(); }
        if ($period === 'week')  { $from = $now->copy()->startOfWeek();  $to = $now->copy()->endOfWeek(); }
        if ($period === 'month') { $from = $now->copy()->startOfMonth(); $to = $now->copy()->endOfMonth(); }

        // Página de ventas (encabezados)
        $salesPage = DB::table('sales as s')
            ->select('s.id','s.sale_date','s.total','s.method_of_payment','s.cash_register_id','s.location_id')
            ->when($from, fn($q)=>$q->where('s.sale_date','>=',$from))
            ->when($to,   fn($q)=>$q->where('s.sale_date','<=',$to))
            ->when($cashRegister, fn($q,$v)=>$q->where('s.cash_register_id',$v))
            ->when($locationId,   fn($q,$v)=>$q->where('s.location_id',$v))
            ->when($q !== '', function($qq) use ($q) {
                $qq->whereExists(function($sub) use ($q) {
                    $sub->select(DB::raw(1))
                        ->from('sale_details as d')
                        ->join('items as i','i.id','=','d.item_id')
                        ->join('products as p','p.id','=','i.product_id')
                        ->leftJoin('sizes as z','z.id','=','i.size_id')
                        ->whereColumn('d.sale_id','s.id')
                        ->where(function($w) use ($q){
                            $w->where('p.name','like',"%{$q}%")
                              ->orWhere('i.code','like',"%{$q}%");
                        });
                });
            })
            ->orderBy('s.sale_date','desc')
            ->paginate($perPage);

        // Detalles de las ventas de esta página
        $saleIds = collect($salesPage->items())->pluck('id')->all();

        $details = DB::table('sale_details as d')
            ->join('items as i','i.id','=','d.item_id')
            ->join('products as p','p.id','=','i.product_id')
            ->leftJoin('sizes as z','z.id','=','i.size_id')
            ->whereIn('d.sale_id', $saleIds)
            ->select(
                'd.sale_id',
                'd.item_id',
                'd.quantity',
                'd.subtotal',
                'i.code',
                DB::raw("CONCAT(p.name, ' - ', COALESCE(z.name,'')) as item_name")
            )
            ->get()
            ->groupBy('sale_id');

        // Respuesta: ventas + sus items
        $data = array_map(function($s) use ($details) {
            $sale = (array) $s;
            $sale['items'] = ($details[$s->id] ?? collect())->values();
            return $sale;
        }, $salesPage->items());

        return response()->json([
            'success' => true,
            'data'    => $data,
            'meta'    => [
                'current_page' => $salesPage->currentPage(),
                'last_page'    => $salesPage->lastPage(),
                'per_page'     => $salesPage->perPage(),
                'total'        => $salesPage->total(),
            ]
        ]);
    }

    /**
     * Mostrar una venta con sus detalles (para “Ver”).
     * GET /api/sales/{id}
     */
    public function show($id)
    {
        // Encabezado
        $sale = DB::table('sales as s')
            ->select('s.id','s.sale_date','s.total','s.method_of_payment','s.cash_register_id','s.location_id')
            ->where('s.id', $id)
            ->first();

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada.',
            ], 404);
        }

        // Detalles
        $items = DB::table('sale_details as d')
            ->join('items as i','i.id','=','d.item_id')
            ->join('products as p','p.id','=','i.product_id')
            ->leftJoin('sizes as z','z.id','=','i.size_id')
            ->where('d.sale_id', $sale->id)
            ->select(
                'd.item_id',
                'd.quantity',
                'd.subtotal',
                'i.code',
                DB::raw("CONCAT(p.name, ' - ', COALESCE(z.name,'')) as item_name")
            )
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'id'                => $sale->id,
                'sale_date'         => $sale->sale_date,
                'total'             => $sale->total,
                'method_of_payment' => $sale->method_of_payment,
                'cash_register_id'  => $sale->cash_register_id,
                'location_id'       => $sale->location_id,
                'items'             => $items,
            ]
        ]);
    }
}
