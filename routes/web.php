<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Wally Reservas
|--------------------------------------------------------------------------
| Archivo principal de rutas. Las rutas se organizan por módulo
| en archivos separados dentro de la carpeta routes/.
*/

// Rutas de autenticación (Laravel Breeze/Sanctum)
Auth::routes();

Route::middleware('guest')->get('/auth/google', [GoogleAuthController::class, 'redirect'])
    ->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('auth.google.callback');

// Cargar módulos de rutas
require __DIR__.'/publico.php';
require __DIR__.'/cliente.php';
require __DIR__.'/recepcionista.php';
require __DIR__.'/admin.php';
