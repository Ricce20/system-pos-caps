<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException; // Importamos la clase de excepción

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        // ESTE BLOQUE LE ENSEÑA A LA APP CÓMO MANEJAR ERRORES DE AUTENTICACIÓN EN LA API
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Autenticación requerida. Por favor, proporciona un token válido.'
                ], 401);
            }
        });
        
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}