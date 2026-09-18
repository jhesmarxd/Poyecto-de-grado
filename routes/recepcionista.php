<?php

use App\Http\Controllers\Recepcionista\CheckinController;
use App\Http\Controllers\Recepcionista\ClienteController;
use App\Http\Controllers\Recepcionista\ReservaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Recepcionista (requieren auth + rol recepcionista o admin)
|--------------------------------------------------------------------------
| Gestión de reservas presenciales, check-in/check-out y búsqueda de clientes.
*/

Route::middleware(['auth', 'recepcionista'])->prefix('recepcionista')->name('recepcionista.')->group(function () {
    // Reservas presenciales
    Route::get('/reservas/hoy', [ReservaController::class, 'hoy'])->name('reservas.hoy');
    Route::get('/reservas/calendario', [ReservaController::class, 'calendario'])->name('reservas.calendario');
    Route::get('/reservas/eventos', [ReservaController::class, 'eventos'])->name('reservas.eventos');
    Route::get('/reservas', [ReservaController::class, 'index'])->name('reservas.index');
    Route::get('/reservas/crear', [ReservaController::class, 'create'])->name('reservas.create');
    Route::get('/reservas/horarios-disponibles', [ReservaController::class, 'horariosDisponibles'])->name('reservas.horarios-disponibles');
    Route::post('/reservas', [ReservaController::class, 'store'])->name('reservas.store');
    Route::post('/reservas/{reserva}/estado', [ReservaController::class, 'cambiarEstado'])->name('reservas.estado');

    // Check-in / Check-out
    Route::get('/checkin', [CheckinController::class, 'index'])->name('checkin');
    Route::post('/checkin/{reserva}', [CheckinController::class, 'checkin'])->name('checkin.store');
    Route::get('/checkout', [CheckinController::class, 'checkoutIndex'])->name('checkout');
    Route::post('/checkout/{reserva}', [CheckinController::class, 'checkout'])->name('checkout.store');

    // Búsqueda de clientes
    Route::get('/clientes/buscar', [ClienteController::class, 'buscar'])->name('clientes.buscar');
    Route::get('/clientes/autocomplete', [ClienteController::class, 'autocomplete'])->name('clientes.autocomplete');
});
