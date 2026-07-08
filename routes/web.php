<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TripWebController;
use App\Http\Controllers\TicketWebController;
use App\Http\Controllers\PackageWebController;
use App\Http\Controllers\SyncPanelController;
use App\Http\Controllers\VehicleWebController;
use App\Http\Controllers\UserWebController;
use App\Http\Controllers\RouteWebController;
use App\Http\Controllers\ClientController;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/trips', [TripWebController::class, 'index'])->name('trips.index');
    Route::post('/trips', [TripWebController::class, 'store'])->name('trips.store');
    Route::delete('/trips/{trip}', [TripWebController::class, 'destroy'])->name('trips.destroy');
    Route::get('/billing/cpe', [BillingController::class, 'consolaCpe'])->name('billing.cpe');
    Route::post('/billing/cpe/{ticket}/reintentar', [BillingController::class, 'reintentarCpe'])->name('billing.cpe.reintentar');
    Route::get('/reports/caja', [ReportController::class, 'liquidacion'])->name('reports.caja');
    
    Route::get('/tickets', [TicketWebController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [TicketWebController::class, 'store'])->name('tickets.store');
    Route::put('/tickets/{ticket}/confirm-reservation', [TicketWebController::class, 'confirmReservation'])->name('tickets.confirm-reservation');
    Route::delete('/tickets/{ticket}', [TicketWebController::class, 'destroy'])->name('tickets.destroy');
    Route::get('/tickets/{ticket}/print', [TicketWebController::class, 'print'])->name('tickets.print');
    Route::post('/tickets/{ticket}/convert-cpe', [TicketWebController::class, 'convertCpe'])->name('tickets.convertCpe');
    Route::patch('/tickets/{ticket}/toggle-payment', [TicketWebController::class, 'togglePayment'])->name('tickets.togglePayment');
    Route::get('/packages', [PackageWebController::class, 'index'])->name('packages.index');
    Route::post('/packages', [PackageWebController::class, 'store'])->name('packages.store');
    Route::get('/packages/{package}/print', [PackageWebController::class, 'print'])->name('packages.print');
    
    // Búsqueda de clientes frecuentes y CRUD
    Route::get('/clientes/search/{documento}', [ClientController::class, 'search'])->name('clientes.search');
    Route::resource('clientes', ClientController::class)->parameters(['clientes' => 'cliente'])->except(['create', 'edit', 'show']);

    Route::get('/billing/sync', [SyncPanelController::class, 'index'])->name('billing.sync');
    Route::get('/settings/vehicles', [VehicleWebController::class, 'index'])->name('settings.vehicles');
    Route::post('/settings/vehicles', [VehicleWebController::class, 'store'])->name('settings.vehicles.store');
    Route::put('/settings/vehicles/{vehicle}', [VehicleWebController::class, 'update'])->name('settings.vehicles.update');
    Route::patch('/settings/vehicles/{vehicle}/toggle', [VehicleWebController::class, 'toggleActivo'])->name('settings.vehicles.toggle');
    
    Route::get('/settings/users', [UserWebController::class, 'index'])->name('settings.users');
    Route::post('/settings/users', [UserWebController::class, 'store'])->name('settings.users.store');
    Route::put('/settings/users/{user}', [UserWebController::class, 'update'])->name('settings.users.update');
    Route::delete('/settings/users/{user}', [UserWebController::class, 'destroy'])->name('settings.users.destroy');
    
    Route::get('/settings/routes', [RouteWebController::class, 'index'])->name('settings.routes');
    Route::post('/settings/routes', [RouteWebController::class, 'store'])->name('settings.routes.store');
    Route::patch('/settings/routes/{route}/toggle', [RouteWebController::class, 'toggleActivo'])->name('settings.routes.toggle');
    Route::post('/settings/routes/{route}/tariffs', [RouteWebController::class, 'storeTariff'])->name('settings.routes.tariffs.store');
    Route::put('/settings/routes/tariffs/{tariff}', [RouteWebController::class, 'updateTariff'])->name('settings.routes.tariffs.update');
    Route::delete('/settings/routes/tariffs/{tariff}', [RouteWebController::class, 'destroyTariff'])->name('settings.routes.tariffs.destroy');
});

require __DIR__ . '/settings.php';
