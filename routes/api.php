<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;

// --- Health check rápido
Route::get('/test', function () {
    return response()->json([
        'ok' => true,
        'at' => now()->toDateTimeString(),
    ]);
});

// --- Auth (público)
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// --- DEBUG: escaneo SIN middleware (quitar en producción)
// Úsalo solo para probar si la request llega a Laravel.
Route::get('/debug/products/scan/{code}', function (string $code) {
    Log::info('DEBUG /products/scan hit', [
        'code' => $code,
        'auth' => request()->header('Authorization'),
        'ip'   => request()->ip(),
        'ua'   => request()->userAgent(),
    ]);

    // Derivamos al controlador para reutilizar la lógica real
    return app(ProductController::class)->findByCode($code);
});

// --- Rutas PROTEGIDAS
Route::middleware(['auth:api'])->group(function () {

    // Búsqueda por código de barras (protegida)
    Route::get('/products/scan/{code}', [ProductController::class, 'findByCode']);

    // Crear venta (recibe carrito, método de pago, etc.)
    Route::post('/sales/create', [SaleController::class, 'create']);

    Route::get('/sales', [\App\Http\Controllers\Api\SaleController::class, 'index']);


    // Perfil / sesión
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me',       [AuthController::class, 'me']);

    // Usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
