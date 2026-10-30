# Código Clave — Sprint 3: Configuración, Integraciones y Reportes

> Fragmentos esenciales de los 12 archivos principales del Sprint 3.

---

## 1. `app/Services/ConfiguracionReservaService.php` — `aplicarCambios()`

```php
<?php

namespace App\Services;

use App\Models\ConfiguracionReserva;
use App\Models\CierreCancha;
use App\Models\Reserva;
use App\Models\ReservaAfectadaConfiguracion;
use App\Services\ReservaService;
use Illuminate\Support\Facades\DB;

class ConfiguracionReservaService
{
    public function aplicarCambios(array $nuevaConfig): ResultadoAplicacion
    {
        return DB::transaction(function () use ($nuevaConfig) {
            // 1. Guardar nueva configuración
            $config = $this->configuracion();
            $config->valor = $nuevaConfig;
            $config->save();

            // 2. Detectar reservas afectadas (futuras, estado activo)
            $reservasAfectadas = $this->detectarReservasAfectadas($nuevaConfig);

            $reprogramadas = 0;
            $canceladas = 0;
            $notificadas = 0;

            // 3. Procesar cada reserva afectada
            foreach ($reservasAfectadas as $reserva) {
                $regla = $this->validarRangoOperativo($reserva->cancha, $reserva->fecha, $reserva->hora_inicio_reserva, $reserva->hora_fin_reserva);

                if ($regla->disponible()) {
                    // Reprogramar: marcar afectada por config
                    $reserva->update(['estado' => Reserva::ESTADO_AFECTADA]);
                    ReservaAfectadaConfiguracion::create([
                        'reserva_id' => $reserva->id,
                        'cambio_id' => $config->id,
                        'accion_tomada' => 'reprogramada',
                        'estado_anterior' => $reserva->estado,
                        'estado_nuevo' => Reserva::ESTADO_AFECTADA,
                    ]);
                    $reprogramadas++;
                } else {
                    // Cancelar por complejo
                    $reserva->update(['estado' => Reserva::ESTADO_CANCELADA_COMPLEJO]);
                    ReservaAfectadaConfiguracion::create([
                        'reserva_id' => $reserva->id,
                        'cambio_id' => $config->id,
                        'accion_tomada' => 'cancelada',
                        'estado_anterior' => $reserva->estado,
                        'estado_nuevo' => Reserva::ESTADO_CANCELADA_COMPLEJO,
                    ]);
                    $canceladas++;
                }

                // 4. Disparar notificaciones y sync async
                $this->notificarCambio($reserva);
                $this->programarSyncGoogle($reserva);
            }

            return new ResultadoAplicacion($reprogramadas, $canceladas, $notificadas, $reservasAfectadas->count());
        });
    }

    private function detectarReservasAfectadas(array $config): \Illuminate\Database\Eloquent\Collection
    {
        return Reserva::query()
            ->activas()
            ->whereDate('fecha', '>=', now()->toDateString())
            ->get()
            ->filter(fn (Reserva $r) => ! $this->validarRangoOperativo($r->cancha, $r->fecha, $r->hora_inicio_reserva, $r->hora_fin_reserva)->disponible());
    }
}
```

---

## 2. `app/Services/GoogleCalendarService.php` — CRUD Eventos

```php
<?php

namespace App\Services;

use App\Models\GoogleCalendarConnection;
use App\Models\Reserva;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;

class GoogleCalendarService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName('Wally Reservas');
        $this->client->setScopes([Calendar::CALENDAR_EVENTS]);
        $this->client->setAccessType('offline');
    }

    private function getCalendarService(GoogleCalendarConnection $connection): Calendar
    {
        $this->client->setAccessToken([
            'access_token' => $connection->access_token,
            'refresh_token' => $connection->refresh_token,
            'expires_at' => $connection->expires_at?->timestamp,
        ]);

        if ($this->client->isAccessTokenExpired()) {
            $this->refreshToken($connection);
        }

        return new Calendar($this->client);
    }

    public function createEvent(Reserva $reserva): string
    {
        $connection = GoogleCalendarConnection::where('enabled', true)->firstOrFail();
        $service = $this->getCalendarService($connection);

        $event = new Event([
            'summary' => "Reserva: {$reserva->cancha->nombre} - {$reserva->usuario->name}",
            'description' => $this->buildDescription($reserva),
            'start' => new EventDateTime([
                'dateTime' => $reserva->iniciaEn()->toIso8601String(),
                'timeZone' => config('app.timezone', 'America/La_Paz'),
            ]),
            'end' => new EventDateTime([
                'dateTime' => $reserva->finalizaEn()->toIso8601String(),
                'timeZone' => config('app.timezone', 'America/La_Paz'),
            ]),
            'extendedProperties' => [
                'private' => ['reserva_id' => (string) $reserva->id],
            ],
        ]);

        $created = $service->events->insert($connection->calendar_id, $event);
        return $created->id;
    }

    public function updateEvent(Reserva $reserva): bool
    {
        $link = $reserva->googleCalendarEvent;
        if (! $link || ! $link->google_event_id) {
            return false;
        }

        $connection = GoogleCalendarConnection::where('enabled', true)->firstOrFail();
        $service = $this->getCalendarService($connection);

        $event = new Event([
            'summary' => "Reserva: {$reserva->cancha->nombre} - {$reserva->usuario->name}",
            'description' => $this->buildDescription($reserva),
            'start' => new EventDateTime([
                'dateTime' => $reserva->iniciaEn()->toIso8601String(),
                'timeZone' => config('app.timezone'),
            ]),
            'end' => new EventDateTime([
                'dateTime' => $reserva->finalizaEn()->toIso8601String(),
                'timeZone' => config('app.timezone'),
            ]),
        ]);

        $service->events->update($connection->calendar_id, $link->google_event_id, $event);
        return true;
    }

    public function deleteEvent(string $googleEventId): bool
    {
        $connection = GoogleCalendarConnection::where('enabled', true)->firstOrFail();
        $service = $this->getCalendarService($connection);

        try {
            $service->events->delete($connection->calendar_id, $googleEventId);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function refreshToken(GoogleCalendarConnection $connection): void
    {
        $this->client->fetchAccessTokenWithRefreshToken($connection->refresh_token);
        $token = $this->client->getAccessToken();
        $connection->update([
            'access_token' => $token['access_token'],
            'expires_at' => now()->addSeconds($token['expires_in']),
        ]);
    }
}
```

---

## 3. `app/Jobs/SyncReservationToGoogleCalendar.php` — Job Idempotente

```php
<?php

namespace App\Jobs;

use App\Models\GoogleCalendarConnection;
use App\Models\GoogleCalendarEvent;
use App\Models\Reserva;
use App\Services\GoogleCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncReservationToGoogleCalendar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // 1min, 2min, 4min...

    public function __construct(
        public readonly int $reservaId
    ) {}

    public function handle(GoogleCalendarService $googleCalendar): void
    {
        $reserva = Reserva::with(['cancha', 'usuario', 'googleCalendarEvent'])->find($this->reservaId);
        if (! $reserva) {
            Log::warning("Sync Google Calendar: Reserva {$this->reservaId} no encontrada");
            return;
        }

        $link = $reserva->googleCalendarEvent;

        // Idempotency: si ya está sincronizado y no hay cambios, salir
        if ($link && $link->sync_status === GoogleCalendarEvent::STATUS_SYNCED && ! $this->hasChanges($reserva, $link)) {
            return;
        }

        // Crear link si no existe
        if (! $link) {
            $calendarId = GoogleCalendarConnection::where('enabled', true)->value('calendar_id') ?? config('services.google_calendar.calendar_id');
            $link = GoogleCalendarEvent::firstOrCreate(
                ['reserva_id' => $reserva->id],
                ['calendar_id' => $calendarId, 'sync_status' => GoogleCalendarEvent::STATUS_PENDING]
            );
        }

        $link->update(['sync_status' => GoogleCalendarEvent::STATUS_PENDING, 'last_error' => null]);

        try {
            if ($reserva->estaCancelada()) {
                if ($link->google_event_id) {
                    $googleCalendar->deleteEvent($link->google_event_id);
                    $link->update(['sync_status' => GoogleCalendarEvent::STATUS_DELETED, 'google_event_id' => null]);
                }
            } elseif ($reserva->estado === Reserva::ESTADO_CONFIRMADA || $reserva->estado === Reserva::ESTADO_COMPLETADA) {
                if ($link->google_event_id) {
                    $googleCalendar->updateEvent($reserva);
                } else {
                    $googleEventId = $googleCalendar->createEvent($reserva);
                    $link->update(['google_event_id' => $googleEventId]);
                }
                $link->update(['sync_status' => GoogleCalendarEvent::STATUS_SYNCED, 'last_synced_at' => now()]);
            }
        } catch (\Exception $e) {
            $link->update(['sync_status' => GoogleCalendarEvent::STATUS_ERROR, 'last_error' => $e->getMessage()]);
            throw $e; // Re-lanza para reintento (backoff exponencial)
        }
    }

    private function hasChanges(Reserva $reserva, GoogleCalendarEvent $link): bool
    {
        // Comparar campos relevantes para evitar sync innecesarios
        return $reserva->updated_at->greaterThan($link->last_synced_at ?? $reserva->created_at);
    }
}
```

---

## 4. `app/Services/ReservaCalendarService.php` — Vistas Calendario

```php
<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\User;
use Carbon\Carbon;

class ReservaCalendarService
{
    public function paraCliente(User $user, Carbon $inicio, Carbon $fin, ?string $estado = null): array
    {
        return $user->reservas()
            ->with(['cancha', 'horario', 'balon'])
            ->whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->get()
            ->map(fn (Reserva $r) => $this->mapToCalendarEvent($r, 'cliente'))
            ->toArray();
    }

    public function paraAdmin(Carbon $inicio, Carbon $fin, array $filtros = []): array
    {
        return Reserva::query()
            ->with(['usuario', 'cancha', 'horario', 'balon'])
            ->whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->when($filtros['estado'] ?? null, fn ($q) => $q->where('estado', $filtros['estado']))
            ->when($filtros['cancha_id'] ?? null, fn ($q) => $q->where('cancha_id', $filtros['cancha_id']))
            ->get()
            ->map(fn (Reserva $r) => $this->mapToCalendarEvent($r, 'admin'))
            ->toArray();
    }

    private function mapToCalendarEvent(Reserva $reserva, string $vista): array
    {
        $color = Reserva::COLORES[$reserva->estado] ?? '#6c757d';
        $title = match ($vista) {
            'cliente' => "{$reserva->cancha->nombre} · {$reserva->hora_inicio_reserva}-{$reserva->hora_fin_reserva}",
            'admin' => "{$reserva->usuario->name} · {$reserva->cancha->nombre} · {$reserva->estadoLabel()}",
            default => $reserva->cancha->nombre,
        };

        return [
            'id' => $reserva->id,
            'title' => $title,
            'start' => $reserva->iniciaEn()->toIso8601String(),
            'end' => $reserva->finalizaEn()->toIso8601String(),
            'color' => $color,
            'extendedProps' => [
                'estado' => $reserva->estado,
                'cancha' => $reserva->cancha->nombre,
                'balon' => $reserva->balonLabel(),
                'monto' => $reserva->monto_total,
                'check_in' => $reserva->check_in_at?->toIso8601String(),
                'check_out' => $reserva->check_out_at?->toIso8601String(),
            ],
        ];
    }
}
```

---

## 5. `app/Services/EstadisticasService.php` — KPIs

```php
<?php

namespace App\Services;

use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EstadisticasService
{
    public function ocupacionPorCancha(Carbon $inicio, Carbon $fin): Collection
    {
        return Reserva::query()
            ->activas()
            ->whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->with('cancha')
            ->get()
            ->groupBy('cancha_id')
            ->map(function ($reservas, $canchaId) use ($inicio, $fin) {
                $cancha = $reservas->first()->cancha;
                $horasTotales = $this->calcularHorasDisponibles($cancha, $inicio, $fin);
                $horasOcupadas = $reservas->sum(fn (Reserva $r) => $r->duracionHoras());
                
                return [
                    'cancha_id' => $canchaId,
                    'cancha_nombre' => $cancha->nombre,
                    'horas_totales' => $horasTotales,
                    'horas_ocupadas' => $horasOcupadas,
                    'porcentaje_ocupacion' => $horasTotales > 0 ? round(($horasOcupadas / $horasTotales) * 100, 1) : 0,
                    'ingresos' => $reservas->sum('monto_total'),
                ];
            })
            ->values();
    }

    public function ingresosPorPeriodo(Carbon $inicio, Carbon $fin): Collection
    {
        return Reserva::query()
            ->activas()
            ->whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->get()
            ->groupBy(fn (Reserva $r) => $r->fecha->format('Y-m-d'))
            ->map(fn ($reservas, $fecha) => [
                'fecha' => $fecha,
                'reservas' => $reservas->count(),
                'ingresos' => $reservas->sum('monto_total'),
            ])
            ->values();
    }

    public function cancelacionesPorPeriodo(Carbon $inicio, Carbon $fin): Collection
    {
        return Reserva::query()
            ->whereIn('estado', [Reserva::ESTADO_CANCELADA, Reserva::ESTADO_CANCELADA_COMPLEJO])
            ->whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<=', $fin->toDateString())
            ->get()
            ->groupBy('estado')
            ->map(fn ($reservas, $estado) => [
                'estado' => $estado,
                'cantidad' => $reservas->count(),
                'ingreso_perdido' => $reservas->sum('monto_total'),
            ])
            ->values();
    }

    private function calcularHorasDisponibles(Cancha $cancha, Carbon $inicio, Carbon $fin): float
    {
        // Suma de horas de horarios disponibles en el rango de fechas
        return $cancha->horarios()
            ->where('disponible', true)
            ->get()
            ->sum(fn (Horario $h) => $h->hora_inicio->diffInHours($h->hora_fin) * $inicio->diffInDays($fin));
    }
}
```

---

## 6. `app/Jobs/EnviarRecordatoriosReservas.php` — Scheduler Diario

```php
<?php

namespace App\Jobs;

use App\Models\Reserva;
use App\Services\ReservaNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnviarRecordatoriosReservas implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ReservaNotificationService $notificaciones): void
    {
        $manana = now()->addDay()->toDateString();
        
        $reservas = Reserva::query()
            ->where('estado', Reserva::ESTADO_CONFIRMADA)
            ->whereDate('fecha', $manana)
            ->with('usuario')
            ->get();

        foreach ($reservas as $reserva) {
            try {
                $notificaciones->enviar($reserva, ReservaNotificationService::TIPO_RECORDATORIO);
                Log::info("Recordatorio enviado para reserva #{$reserva->id}");
            } catch (\Exception $e) {
                Log::error("Error enviando recordatorio reserva #{$reserva->id}: {$e->getMessage()}");
            }
        }
    }
}
```

**Schedule en `app/Console/Kernel.php`**:
```php
protected function schedule(Schedule $schedule): void
{
    $schedule->job(new EnviarRecordatoriosReservas())->dailyAt('08:00');
}
```

---

## 7. `app/Http/Controllers/Admin/ConfiguracionReservaController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateConfiguracionReservaRequest;
use App\Services\ConfiguracionReservaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConfiguracionReservaController extends Controller
{
    public function __construct(private readonly ConfiguracionReservaService $configService) {}

    public function index(): View
    {
        $config = $this->configService->configuracion();
        return view('admin.configuracion-reservas.index', compact('config'));
    }

    public function review(UpdateConfiguracionReservaRequest $request): View
    {
        $resultado = $this->configService->previsualizarCambios($request->validated());
        return view('admin.configuracion-reservas.review', compact('resultado'));
    }

    public function update(UpdateConfiguracionReservaRequest $request): RedirectResponse
    {
        $resultado = $this->configService->aplicarCambios($request->validated());
        return redirect()->route('admin.configuracion-reservas.index')
            ->with('status', "Cambios aplicados: {$resultado->reprogramadas} reprogramadas, {$resultado->canceladas} canceladas.");
    }
}
```

---

## 8. `app/Http/Controllers/Admin/DashboardController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EstadisticasService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly EstadisticasService $stats) {}

    public function index(): View
    {
        $hoy = now();
        $inicioMes = $hoy->copy()->startOfMonth();
        $finMes = $hoy->copy()->endOfMonth();

        return view('admin.dashboard', [
            'ocupacion' => $this->stats->ocupacionPorCancha($inicioMes, $finMes),
            'ingresos' => $this->stats->ingresosPorPeriodo($inicioMes, $finMes),
            'cancelaciones' => $this->stats->cancelacionesPorPeriodo($inicioMes, $finMes),
            'reservasHoy' => Reserva::query()->whereDate('fecha', $hoy)->count(),
            'usuariosActivos' => User::query()->whereHas('reservas', fn ($q) => $q->whereDate('fecha', '>=', $inicioMes))->count(),
        ]);
    }
}
```

---

## 9. `app/Http/Controllers/Admin/GoogleCalendarController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoogleCalendarConnection;
use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GoogleCalendarController extends Controller
{
    public function __construct(private readonly GoogleCalendarService $gcal) {}

    public function connect(): RedirectResponse
    {
        $url = $this->gcal->getAuthUrl();
        return redirect()->away($url);
    }

    public function callback(Request $request): RedirectResponse
    {
        $this->gcal->handleCallback($request);
        return redirect()->route('admin.google-calendar.index')->with('status', 'Google Calendar conectado.');
    }

    public function disconnect(): RedirectResponse
    {
        GoogleCalendarConnection::query()->update(['enabled' => false]);
        return back()->with('status', 'Google Calendar desconectado.');
    }

    public function sync(): \Illuminate\Http\JsonResponse
    {
        // Dispara sync masivo de reservas confirmadas futuras
        return response()->json(['status' => 'Sincronización iniciada']);
    }
}
```

---

## 10. `app/Models/ConfiguracionReserva.php` — JSON Config

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionReserva extends Model
{
    use HasFactory;

    protected $fillable = ['clave', 'valor', 'descripcion', 'tipo'];

    protected function casts(): array
    {
        return ['valor' => 'array']; // JSON cast automático
    }

    // Estructura esperada en 'valor':
    // [
    //   'duraciones_permitidas' => [60, 90, 120],
    //   'anticipacion_minima_horas' => 2,
    //   'anticipacion_maxima_dias' => 30,
    //   'ventana_cancelacion_horas' => 4,
    //   'permitir_checkin_antes_minutos' => 15,
    // ]
}
```

---

## 11. `database/migrations/2026_10_01_000001_create_reservation_policy_tables.php` — Fragmento

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('configuracion_reservas', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();
            $table->json('valor');
            $table->text('descripcion')->nullable();
            $table->string('tipo')->default('json');
            $table->timestamps();
        });

        Schema::create('cierres_cancha', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cancha_id')->constrained()->cascadeOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('motivo');
            $table->boolean('recurrente')->default(false);
            $table->unsignedTinyInteger('dia_semana')->nullable(); // 1=Lun..7=Dom
            $table->timestamps();
        });

        Schema::create('reservas_afectadas_configuracion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cambio_id')->constrained('configuracion_reservas')->cascadeOnDelete();
            $table->enum('accion_tomada', ['reprogramada', 'cancelada', 'notificada']);
            $table->string('estado_anterior');
            $table->string('estado_nuevo');
            $table->timestamps();
        });
    }
};
```

---

## 12. `app/Notifications/ReservaNotification.php` — Fragmento

```php
<?php

namespace App\Notifications;

use App\Models\Reserva;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Reserva $reserva,
        public readonly string $tipo
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database']; // email + notificación en app
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = match ($this->tipo) {
            self::TIPO_CREADA => "Reserva #{$this->reserva->id} confirmada",
            self::TIPO_CANCELADA => "Reserva #{$this->reserva->id} cancelada",
            self::TIPO_RECORDATORIO => "Recordatorio: Reserva mañana #{$this->reserva->id}",
            self::TIPO_INICIADA => "Check-in realizado: Reserva #{$this->reserva->id}",
            self::TIPO_FINALIZADA => "Reserva #{$this->reserva->id} finalizada",
            default => "Actualización de reserva #{$this->reserva->id}",
        };

        return (new MailMessage)
            ->subject($subject)
            ->line($this->getMessageBody())
            ->action('Ver reserva', url("/reservas/{$this->reserva->id}"));
    }

    public function toArray($notifiable): array
    {
        return [
            'reserva_id' => $this->reserva->id,
            'tipo' => $this->tipo,
            'mensaje' => $this->getMessageBody(),
            'cancha' => $this->reserva->cancha->nombre,
            'fecha' => $this->reserva->fecha->format('d/m/Y'),
            'hora' => "{$this->reserva->hora_inicio_reserva} - {$this->reserva->hora_fin_reserva}",
        ];
    }
}
```