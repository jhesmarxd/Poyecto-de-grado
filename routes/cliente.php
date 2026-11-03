<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Cliente (requieren autenticación)
|--------------------------------------------------------------------------
| Reservas propias, mis reservas y perfil del usuario autenticado.
*/

Route::middleware('auth')->group(function () {
    // Reservas del cliente
    Route::get('/reservas/crear', [ReservaController::class, 'crear'])->name('reservas.crear');
    Route::post('/reservas', [ReservaController::class, 'store'])->name('reservas.store');
    Route::post('/reservas/celular', [ReservaController::class, 'guardarCelular'])->name('reservas.celular');
    Route::get('/mis-reservas', [ReservaController::class, 'mis'])->name('reservas.mis');
    Route::get('/mis-reservas/eventos', [ReservaController::class, 'eventos'])->name('reservas.mis.eventos');
    Route::post('/reservas/{reserva}/cancelar', [ReservaController::class, 'cancelar'])->name('reservas.cancelar');

    // Notificaciones internas del cliente
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notificaciones.index');
    Route::post('/notificaciones/leer-todas', [NotificationController::class, 'leerTodas'])->name('notificaciones.leer-todas');
    Route::post('/notificaciones/{notification}/leer', [NotificationController::class, 'leer'])->name('notificaciones.leer');

    // Home del usuario autenticado
    Route::get('/home', [HomeController::class, 'index'])->name('home');
});

// Perfil del usuario autenticado
Route::middleware('auth')->prefix('perfil')->name('perfil.')->group(function () {
    Route::get('/', [ProfileController::class, 'edit'])->name('edit');
    Route::put('/', [ProfileController::class, 'update'])->name('update');
    Route::get('/google/vincular', [GoogleAuthController::class, 'redirectForLink'])->name('google.link');
    Route::delete('/google', [GoogleAuthController::class, 'unlink'])->name('google.unlink');
});
