<?php

use App\Http\Controllers\Admin\AjusteController;
use App\Http\Controllers\Admin\BalonController as AdminBalonController;
use App\Http\Controllers\Admin\CanchaController as AdminCanchaController;
use App\Http\Controllers\Admin\ChatbotMetricasController;
use App\Http\Controllers\Admin\ConversacionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GoogleCalendarController;
use App\Http\Controllers\Admin\HorarioController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Admin\ReservaController as AdminReservaController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Administración (solo administradores)
|--------------------------------------------------------------------------
| Panel de administración: dashboard, CRUD de entidades, reportes.
*/

Route::middleware(['auth', 'admin'])->group(function () {
    // Dashboard
    Route::get('/admin', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Gestión de usuarios
    Route::prefix('admin/usuarios')->name('admin.usuarios.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/crear', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{usuario}/editar', [UserController::class, 'edit'])->name('edit');
        Route::put('/{usuario}', [UserController::class, 'update'])->name('update');
        Route::delete('/{usuario}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Gestión de canchas
    Route::prefix('admin/canchas')->name('admin.canchas.')->group(function () {
        Route::get('/', [AdminCanchaController::class, 'index'])->name('index');
        Route::get('/crear', [AdminCanchaController::class, 'create'])->name('create');
        Route::post('/', [AdminCanchaController::class, 'store'])->name('store');
        Route::get('/{cancha}/editar', [AdminCanchaController::class, 'edit'])->name('edit');
        Route::put('/{cancha}', [AdminCanchaController::class, 'update'])->name('update');
        Route::delete('/{cancha}', [AdminCanchaController::class, 'destroy'])->name('destroy');
    });

    // Gestión de balones
    Route::prefix('admin/balones')->name('admin.balones.')->group(function () {
        Route::get('/', [AdminBalonController::class, 'index'])->name('index');
        Route::get('/nuevo', [AdminBalonController::class, 'create'])->name('create');
        Route::post('/', [AdminBalonController::class, 'store'])->name('store');
        Route::get('/{balon}/editar', [AdminBalonController::class, 'edit'])->name('edit');
        Route::put('/{balon}', [AdminBalonController::class, 'update'])->name('update');
        Route::delete('/{balon}', [AdminBalonController::class, 'destroy'])->name('destroy');
    });

    // Reportes de reservas
    Route::prefix('admin/reportes')->name('admin.reportes.')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('index');
        Route::get('/exportar', [ReporteController::class, 'exportar'])->name('exportar');
    });

    // Gestión de reservas
    Route::prefix('admin/reservas')->name('admin.reservas.')->group(function () {
        Route::get('/', [AdminReservaController::class, 'index'])->name('index');
        Route::get('/calendario', [AdminReservaController::class, 'calendario'])->name('calendario');
        Route::get('/eventos', [AdminReservaController::class, 'eventos'])->name('eventos');
        Route::post('/{reserva}/estado', [AdminReservaController::class, 'cambiarEstado'])->name('estado');
        Route::delete('/{reserva}', [AdminReservaController::class, 'destroy'])->name('destroy');
    });

    // Historial de conversaciones del chatbot
    Route::prefix('admin/chatbot')->name('admin.chatbot.')->group(function () {
        Route::get('/metricas', [ChatbotMetricasController::class, 'index'])->name('metricas');
        Route::get('/', [ConversacionController::class, 'index'])->name('conversaciones');
        Route::get('/{conversacion}', [ConversacionController::class, 'show'])->name('conversaciones.show');
    });

    // Administración de horarios por cancha
    Route::prefix('admin')->name('admin.horarios.')->group(function () {
        Route::get('/horarios', [HorarioController::class, 'index'])->name('index');
        Route::get('/canchas/{cancha}/horarios/crear', [HorarioController::class, 'create'])->name('create');
        Route::post('/canchas/{cancha}/horarios', [HorarioController::class, 'store'])->name('store');
        Route::post('/canchas/{cancha}/horarios/configurar', [HorarioController::class, 'storeBulk'])->name('bulk');
        Route::post('/canchas/{cancha}/horarios/cierres', [HorarioController::class, 'storeClosure'])->name('closures.store');
        Route::delete('/canchas/{cancha}/horarios/cierres/{cierre}', [HorarioController::class, 'destroyClosure'])->name('closures.destroy');
        Route::get('/canchas/{cancha}/horarios/{horario}/editar', [HorarioController::class, 'edit'])->name('edit');
        Route::put('/canchas/{cancha}/horarios/{horario}', [HorarioController::class, 'update'])->name('update');
        Route::delete('/canchas/{cancha}/horarios/{horario}', [HorarioController::class, 'destroy'])->name('destroy');
    });

    // Personalización visual y horario informativo del sitio
    Route::prefix('admin/ajustes')->name('admin.ajustes.')->group(function () {
        Route::get('/', [AjusteController::class, 'index'])->name('index');
        Route::put('/', [AjusteController::class, 'update'])->name('update');
    });

    // Integración empresarial con Google Calendar
    Route::prefix('admin/integraciones/google-calendar')->name('admin.integraciones.google-calendar.')->group(function () {
        Route::get('/', [GoogleCalendarController::class, 'index'])->name('index');
        Route::get('/conectar', [GoogleCalendarController::class, 'connect'])->name('connect');
        Route::get('/callback', [GoogleCalendarController::class, 'callback'])->name('callback');
        Route::put('/', [GoogleCalendarController::class, 'update'])->name('update');
        Route::delete('/', [GoogleCalendarController::class, 'disconnect'])->name('disconnect');
        Route::post('/sincronizar', [GoogleCalendarController::class, 'syncUpcoming'])->name('sync');
        Route::post('/verificar', [GoogleCalendarController::class, 'verify'])->name('verify');
        Route::post('/reservas/{reserva}/reintentar', [GoogleCalendarController::class, 'retry'])->name('retry');
    });
});
