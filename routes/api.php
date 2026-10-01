<?php

use App\Http\Controllers\Api\AnimalController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\PrecioController;
use App\Http\Controllers\Api\ProduccionController;
use Illuminate\Support\Facades\Route;

// API de la app Android (Plan Completo). Prefijo /api; token Sanctum.

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/registro', [AuthController::class, 'register']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Lo demás exige un plan vigente que incluya la app.
    Route::middleware('acceso.app')->group(function () {
        Route::get('/tipos-animal', [CatalogoController::class, 'tipos']);
        Route::get('/cortes', [CatalogoController::class, 'cortes']);

        Route::get('/animales', [AnimalController::class, 'index']);
        Route::post('/animales', [AnimalController::class, 'store'])->middleware('permiso:ingresos');

        // Ver producciones (Inicio de la app) está abierto; cargarlas pide el permiso.
        Route::get('/producciones', [ProduccionController::class, 'index']);
        Route::get('/producciones/{produccion}', [ProduccionController::class, 'show'])->whereNumber('produccion');
        Route::middleware('permiso:producciones')->group(function () {
            Route::post('/producciones', [ProduccionController::class, 'store']);
            Route::post('/producciones/pendientes', [ProduccionController::class, 'iniciar']);
            Route::post('/producciones/{produccion}/pesadas', [ProduccionController::class, 'agregarPesada'])->whereNumber('produccion');
            Route::delete('/producciones/{produccion}/pesadas/{pesada}', [ProduccionController::class, 'quitarPesada'])->whereNumber(['produccion', 'pesada']);
            Route::post('/producciones/{produccion}/terminar', [ProduccionController::class, 'terminar'])->whereNumber('produccion');
            Route::delete('/producciones/{produccion}', [ProduccionController::class, 'cancelar'])->whereNumber('produccion');
        });

        Route::put('/precios', [PrecioController::class, 'update']);
    });
});
