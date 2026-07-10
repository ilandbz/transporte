<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\SyncStatusController;
use App\Http\Controllers\Api\GpsController;
use App\Http\Controllers\Api\ConsultaController;
use App\Http\Controllers\Api\TestSunatController;
use App\Http\Controllers\Api\CatalogController;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Catálogo
    Route::get('routes', [CatalogController::class, 'routes']);
    Route::get('vehicles', [CatalogController::class, 'vehicles']);
    Route::get('branches', [CatalogController::class, 'branches']);

    // Viajes (manifiestos)
    Route::get('trips', [TripController::class, 'index']);
    Route::post('trips', [TripController::class, 'store']);
    Route::get('trips/{trip}', [TripController::class, 'show']);
    Route::patch('trips/{trip}/close', [TripController::class, 'close']);
    Route::get('trips/{trip}/seats', [TripController::class, 'seats']);
    Route::get('trips/{trip}/tickets', [TripController::class, 'tickets']);
    Route::get('trips/{trip}/packages', [TripController::class, 'packages']);
    Route::post('trips/{trip}/gps', [GpsController::class, 'store']);

    // Pasajes
    Route::post('tickets', [TicketController::class, 'store']);
    Route::get('tickets/{ticket}', [TicketController::class, 'show']);
    Route::get('tickets/{ticket}/pdf', [TicketController::class, 'pdf']);
    Route::patch('tickets/{ticket}/anular', [TicketController::class, 'anular']);

    // Encomiendas
    Route::post('packages', [PackageController::class, 'store']);
    Route::get('packages/{package}/qr', [PackageController::class, 'qr']);
    Route::patch('packages/{package}/deliver', [PackageController::class, 'deliver']);
    Route::patch('packages/{package}', [PackageController::class, 'update']);
    Route::patch('packages/{package}/anular', [PackageController::class, 'anular']);

    // Sincronización offline
    Route::post('sync/batch', [SyncController::class, 'batch']);
    Route::get('sync/status', [SyncStatusController::class, 'status']);
    Route::get('sync/errors', [SyncStatusController::class, 'errors']);

    // Consulta identidad
    Route::get('consulta/dni/{dni}', [ConsultaController::class, 'dni']);
    Route::get('consulta/ruc/{ruc}', [ConsultaController::class, 'ruc']);

    // Rutas de prueba SUNAT — eliminar en producción
    Route::prefix('test')->group(function () {
        Route::get('sunat/conexion', [TestSunatController::class, 'conexion']);
        Route::post('sunat/boleta/{ticket}', [TestSunatController::class, 'boleta']);
    });
});
