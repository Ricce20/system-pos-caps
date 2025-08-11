<?php

use App\Http\Controllers\SalesController;

// Si NO tienes todavía resource:
Route::resource('sales', SalesController::class)->only(['index','show']);

// Si ya tenías alguna ruta a "sales/{id}", asegúrate que apunte a SalesController@show
