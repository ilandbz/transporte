<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\GpsController;
use App\Http\Controllers\Api\ConsultaController;
use App\Http\Controllers\Api\TestSunatController;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Viajes (manifiestos)
    Route::post('trips', [TripController::class, 'store']);
    Route::patch('trips/{trip}/close', [TripController::class, 'close']);
    Route::get('trips/{trip}/seats', [TripController::class, 'seats']);
    Route::post('trips/{trip}/gps', [GpsController::class, 'store']);

    // Pasajes
    Route::post('tickets', [TicketController::class, 'store']);
    Route::get('tickets/{ticket}', [TicketController::class, 'show']);

    // Encomiendas
    Route::post('packages', [PackageController::class, 'store']);
    Route::get('packages/{package}/qr', [PackageController::class, 'qr']);
    Route::patch('packages/{package}/deliver', [PackageController::class, 'deliver']);

    // Sincronización offline
    Route::post('sync/batch', [SyncController::class, 'batch']);

    // Consulta identidad
    Route::get('consulta/dni/{dni}', [ConsultaController::class, 'dni']);
    Route::get('consulta/ruc/{ruc}', [ConsultaController::class, 'ruc']);

    // Rutas de prueba SUNAT — eliminar en producción
    Route::prefix('test')->group(function () {
        Route::get('sunat/conexion', [TestSunatController::class, 'conexion']);
        Route::post('sunat/boleta/{ticket}', [TestSunatController::class, 'boleta']);
    });
});
