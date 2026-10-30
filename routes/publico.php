<?php

use App\Http\Controllers\BalonController;
use App\Http\Controllers\CanchaController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DisponibilidadController;
use App\Http\Controllers\InicioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas (sin autenticación)
|--------------------------------------------------------------------------
| Página de inicio, catálogo de canchas y disponibilidad.
*/

// Página de inicio pública
Route::get('/', [InicioController::class, 'index'])->name('inicio');

// Catálogo público de canchas
Route::get('/canchas', [CanchaController::class, 'index'])->name('canchas.index');
Route::get('/canchas/{cancha}', [CanchaController::class, 'show'])->name('canchas.show');

// Versión del catálogo de balones (para detectar cambios en tiempo real)
Route::get('/balones/version', [BalonController::class, 'version'])->name('balones.version');

// Consulta pública de disponibilidad
Route::get('/disponibilidad', [DisponibilidadController::class, 'index'])->name('disponibilidad.index');

// Chatbot exclusivo para usuarios registrados con una sesión activa.
Route::post('/chatbot/mensaje', [ChatbotController::class, 'mensaje'])
    ->middleware(['chatbot.auth', 'throttle:chatbot'])
    ->name('chatbot.mensaje');

Route::post('/chatbot/nueva-conversacion', [ChatbotController::class, 'nuevaConversacion'])
    ->middleware(['chatbot.auth', 'throttle:chatbot'])
    ->name('chatbot.nueva-conversacion');
