# Código Clave — Sprint 2: Canchas, Horarios y Reservas Core

> Fragmentos esenciales de los 12 archivos principales del Sprint 2.

---

## 1. `app/Services/ReservaService.php` — `crearReserva()` (Core)

```php
<?php

namespace App\Services;

use App\Jobs\SyncReservationToGoogleCalendar;
use App\Models\Balon;
use App\Models\Cancha;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReservaService
{
    public function crearReserva(User $user, array $data): Reserva
    {
        return DB::transaction(function () use ($user, $data) {
            // 1. Bloqueo pesimista en orden FIJO (previene deadlocks)
            $cancha = Cancha::disponibles()->lockForUpdate()->findOrFail($data['cancha_id']);
            $horario = $cancha->horarios()
                ->where('disponible', true)
                ->lockForUpdate()
                ->findOrFail($data['horario_id']);

            $fecha = $data['fecha'];

            // 2. Validaciones de negocio
            if (! $this->esFechaValida($fecha)) {
                abort(422, 'La fecha no es válida.');
            }

            $diaSemana = Carbon::parse($fecha)->dayOfWeekIso;
            if ((int) $horario->dia_semana !== $diaSemana) {
                abort(422, 'El horario no corresponde al día de la fecha seleccionada.');
            }

            $horaFin = Carbon::parse($data['hora_fin'] ?? $horario->hora_fin)->format('H:i:s');
            $inicio = Carbon::parse($fecha . ' ' . $horario->hora_inicio);
            $fin = Carbon::parse($fecha . ' ' . $horaFin);
            if ($fin->lessThanOrEqualTo($inicio)) {
                abort(422, 'La hora final debe ser posterior a la hora inicial.');
            }

            // 3. Anti-solapamiento CANCHA (rango abierto: inicio < fin_existente AND fin > inicio_existente)
            if ($this->periodoOcupado($cancha->id, $fecha, $horario->hora_inicio, $horaFin)) {
                abort(409, 'Ese horario ya fue reservado. Por favor elige otro.');
            }

            // 4. Políticas configurables (duraciones, ventanas, horarios)
            $politica = $this->politicaReservas ??= app(PoliticaReservasService::class);
            $regla = $politica->validarRangoOperativo($cancha, $fecha, $horario->hora_inicio, $horaFin);
            if (! $regla->disponible()) {
                abort(422, $regla->mensaje);
            }

            // 5. Validar franjas consecutivas habilitadas
            $franjas = $cancha->horarios()
                ->where('dia_semana', $diaSemana)
                ->where('disponible', true)
                ->where('hora_inicio', '>=', $horario->hora_inicio)
                ->where('hora_fin', '<=', $horaFin)
                ->where(fn ($q) => $q->whereNull('vigente_desde')->orWhereDate('vigente_desde', '<=', $fecha))
                ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $fecha))
                ->orderBy('hora_inicio')
                ->lockForUpdate()
                ->get();

            $siguienteInicio = $horario->hora_inicio;
            foreach ($franjas as $franja) {
                if ($franja->hora_inicio !== $siguienteInicio) {
                    abort(422, 'El rango solicitado no está formado por franjas consecutivas.');
                }
                $siguienteInicio = $franja->hora_fin;
            }
            if ($franjas->isEmpty() || $siguienteInicio !== $horaFin) {
                abort(422, 'El rango solicitado no está completamente habilitado.');
            }

            // 6. Balón: bloqueo + validación anti-solapamiento BALÓN (compartido por todo el recinto)
            $balonId = $data['balon_id'] ?? null;
            $balon = $balonId
                ? Balon::query()->lockForUpdate()->findOrFail($balonId)
                : null;

            if ($balon && $this->balonOcupado($balon->id, $fecha, $horario, $horaFin)) {
                abort(409, 'Ese balón ya fue reservado para esa fecha y hora. Elige otro balón o trae el tuyo.');
            }

            // 7. Crear reserva
            try {
                $duracionMinutos = (int) $inicio->diffInMinutes($fin);
                return Reserva::create([
                    'user_id' => $user->id,
                    'cancha_id' => $cancha->id,
                    'horario_id' => $horario->id,
                    'fecha' => $fecha,
                    'hora_inicio_reserva' => $horario->hora_inicio,
                    'hora_fin_reserva' => $horaFin,
                    'duracion_minutos' => $duracionMinutos,
                    'monto_total' => $this->calcularMonto((float) $cancha->precio_por_hora, $balonId, $duracionMinutos),
                    'tipo_balon' => $this->determinarTipoBalon($balon),
                    'balon_id' => $balonId,
                    'estado' => Reserva::ESTADO_PENDIENTE,
                    'observaciones' => $data['observaciones'] ?? null,
                ]);
            } catch (QueryException $exception) {
                $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
                if (in_array($sqlState, ['19', '23000'], true)) {
                    abort(409, 'La franja acaba de ser reservada por otra solicitud. Elige otra.');
                }
                throw $exception;
            }
        });
    }

    // Anti-solapamiento CANCHA
    public function periodoOcupado(int $canchaId, string $fecha, string $horaInicio, string $horaFin): bool
    {
        return Reserva::query()
            ->activas()
            ->deCanchaEnFecha($canchaId, $fecha)
            ->where('hora_inicio_reserva', '<', $horaFin)
            ->where('hora_fin_reserva', '>', $horaInicio)
            ->exists();
    }

    // Anti-solapamiento BALÓN (compartido por recinto)
    public function balonOcupado(int $balonId, string $fecha, Horario $horario, ?string $horaFin = null): bool
    {
        $horaFin ??= $horario->hora_fin;
        return Reserva::query()
            ->activas()
            ->whereDate('fecha', $fecha)
            ->where('balon_id', $balonId)
            ->where('hora_inicio_reserva', '<', $horaFin)
            ->where('hora_fin_reserva', '>', $horario->hora_inicio)
            ->exists();
    }

    public function calcularMonto(float $precioBaseCancha, ?int $balonId, int $duracionMinutos = 60): float
    {
        return Reserva::calcularMonto($precioBaseCancha, $balonId) * ($duracionMinutos / 60);
    }

    private function programarSincronizacionGoogle(Reserva $reserva): void
    {
        // ... dispara SyncReservationToGoogleCalendar::dispatch($reserva->id)->afterCommit()
    }
}
```

**Puntos críticos**:
- **Orden fijo de locks**: Cancha → Horario → Balón (previene deadlocks)
- **Rango abierto** para solapamiento: `inicio < fin_existente AND fin > inicio_existente`
- **Franjas consecutivas** validadas con `lockForUpdate` en horarios
- **Catch QueryException** con SQLSTATE 19/23000 → 409 Conflict (race condition)
- **Job async** `afterCommit()` solo si transacción exitosa

---

## 2. `app/Services/ReservaService.php` — `registrarCheckIn()` y `registrarCheckOut()`

```php
    public function registrarCheckIn(Reserva $reserva): void
    {
        DB::transaction(function () use ($reserva) {
            $reservaBloqueada = Reserva::query()->lockForUpdate()->findOrFail($reserva->id);

            abort_unless(
                $reservaBloqueada->estado === Reserva::ESTADO_CONFIRMADA
                && $reservaBloqueada->fecha->isToday()
                && $reservaBloqueada->check_in_at === null,
                422,
                'Solo se puede registrar la entrada de una reserva confirmada de hoy.'
            );

            $reservaBloqueada->update(['check_in_at' => now()]);
        });

        $this->notificar($reserva->fresh(), ReservaNotificationService::TIPO_INICIADA);
    }

    public function registrarCheckOut(Reserva $reserva): void
    {
        DB::transaction(function () use ($reserva) {
            $reservaBloqueada = Reserva::query()->lockForUpdate()->findOrFail($reserva->id);

            abort_unless(
                in_array($reservaBloqueada->estado, [Reserva::ESTADO_CONFIRMADA, Reserva::ESTADO_COMPLETADA], true)
                && $reservaBloqueada->fecha->isToday()
                && $reservaBloqueada->check_in_at !== null
                && $reservaBloqueada->check_out_at === null,
                422,
                'Solo se puede registrar la salida después de una entrada de hoy.'
            );

            $reservaBloqueada->update([
                'check_out_at' => now(),
                'estado' => Reserva::ESTADO_COMPLETADA,
            ]);
        });

        $this->programarSincronizacionGoogle($reserva);
        $this->notificar($reserva->fresh(), ReservaNotificationService::TIPO_FINALIZADA);
    }
```

---

## 3. `app/Services/PoliticaReservasService.php` — `validarRangoOperativo()`

```php
<?php

namespace App\Services;

use App\Models\Cancha;
use App\Models\ConfiguracionReserva;
use App\Models\Horario;
use Carbon\Carbon;

class PoliticaReservasService
{
    public function validarRangoOperativo(Cancha $cancha, string $fecha, string $horaInicio, string $horaFin): ResultadoValidacion
    {
        $config = $this->configuracion();
        $inicio = Carbon::parse($fecha . ' ' . $horaInicio);
        $fin = Carbon::parse($fecha . ' ' . $horaFin);
        $duracionMinutos = $inicio->diffInMinutes($fin);

        // 1. Duraciones permitidas
        $duraciones = array_map('intval', $config->duraciones_permitidas ?? [60, 90, 120]);
        if (! in_array($duracionMinutos, $duraciones, true)) {
            return new ResultadoValidacion(false, 'La duración permitida debe ser de ' . implode(', ', $duraciones) . ' minutos.');
        }

        // 2. Anticipación mínima
        $anticipacionMinima = (int) ($config->anticipacion_minima_horas ?? 2);
        if ($inicio->lessThanOrEqualTo(Carbon::now()->addHours($anticipacionMinima))) {
            return new ResultadoValidacion(false, "La reserva debe hacerse con al menos {$anticipacionMinima} horas de anticipación.");
        }

        // 3. Anticipación máxima
        $anticipacionMaxima = (int) ($config->anticipacion_maxima_dias ?? 30);
        if ($inicio->greaterThan(Carbon::now()->addDays($anticipacionMaxima))) {
            return new ResultadoValidacion(false, "No se pueden hacer reservas con más de {$anticipacionMaxima} días de anticipación.");
        }

        // 4. Ventana de cancelación (para validar si se puede cancelar luego)
        // ...

        return new ResultadoValidacion(true, 'Disponible');
    }

    public function configuracion(): ConfiguracionReserva
    {
        return ConfiguracionReserva::firstOrCreate(
            ['clave' => 'politicas_reserva'],
            ['valor' => [], 'descripcion' => 'Políticas de reserva del sistema']
        );
    }
}
```

---

## 4. `app/Models/Reserva.php` — Domain Model Rico

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Reserva extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_CONFIRMADA = 'confirmada';
    public const ESTADO_CANCELADA = 'cancelada';
    public const ESTADO_COMPLETADA = 'completada';
    public const ESTADO_AFECTADA = 'afectada_por_configuracion';
    public const ESTADO_CANCELADA_COMPLEJO = 'cancelada_por_complejo';
    public const ESTADO_REPROGRAMADA = 'reprogramada';

    public const BALON_PROPIO = 'propio';
    public const BALON_PRESTADO = 'prestado';
    public const BALON_PREMIUM = 'premium';

    protected $fillable = [
        'user_id', 'reprogramada_desde_id', 'cancha_id', 'horario_id', 'fecha',
        'hora_inicio_reserva', 'hora_fin_reserva', 'duracion_minutos', 'monto_total',
        'tipo_balon', 'balon_id', 'estado', 'observaciones', 'check_in_at', 'check_out_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'hora_inicio_reserva' => 'string',
            'hora_fin_reserva' => 'string',
            'duracion_minutos' => 'integer',
            'monto_total' => 'decimal:2',
            'estado' => 'string',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }

    // Cálculo monto estático (reutilizable)
    public static function calcularMonto(float $precioBaseCancha, ?int $balonId): float
    {
        if ($balonId) {
            $balon = Balon::find($balonId);
            return $balon ? (float) $balon->precio_por_hora : $precioBaseCancha;
        }
        return $precioBaseCancha;
    }

    // Scopes
    public function scopeActivas(Builder $query): Builder
    {
        return $query->whereNotIn('estado', [
            self::ESTADO_CANCELADA,
            self::ESTADO_CANCELADA_COMPLEJO,
            self::ESTADO_REPROGRAMADA,
        ]);
    }

    public function scopeParaFecha(Builder $query, string $fecha): Builder
    {
        return $query->where('fecha', $fecha);
    }

    public function scopeDeCanchaEnFecha(Builder $query, int $canchaId, string $fecha): Builder
    {
        return $query->where('cancha_id', $canchaId)->whereDate('fecha', $fecha);
    }

    // Helpers de estado
    public function estaCancelada(): bool
    {
        return in_array($this->estado, [
            self::ESTADO_CANCELADA,
            self::ESTADO_CANCELADA_COMPLEJO,
            self::ESTADO_REPROGRAMADA,
        ], true);
    }

    public function esCancelablePorCliente(): bool
    {
        return in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_CONFIRMADA], true)
            && $this->fecha->isAfter(Carbon::today());
    }

    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function estadoBadge(): string
    {
        return self::CLASES_BADGE[$this->estado] ?? 'bg-secondary';
    }

    // Estado operativo para recepción
    public function estadoOperacionLabel(): string
    {
        if ($this->check_out_at !== null || ($this->estado === self::ESTADO_COMPLETADA && $this->fecha->isPast())) {
            return 'Finalizada';
        }
        if ($this->estaEnCurso()) {
            return 'En curso';
        }
        return $this->estadoLabel();
    }

    public function iniciaEn(): Carbon
    {
        return Carbon::parse(
            $this->fecha->format('Y-m-d') . ' ' . ($this->hora_inicio_reserva ?: $this->horario->hora_inicio),
            config('app.timezone', 'UTC')
        );
    }
}
```

---

## 5. `app/Models/Cancha.php` — `horariosDisponiblesPara()`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cancha extends Model
{
    use HasFactory;

    public const ESTADO_DISPONIBLE = 'disponible';
    public const ESTADO_MANTENIMIENTO = 'mantenimiento';
    public const ESTADO_INACTIVA = 'inactiva';

    protected $fillable = [
        'nombre', 'descripcion', 'ubicacion', 'precio_por_hora', 'estado',
        'imagen', 'color_malla', 'altura_malla', 'tipo_piso', 'tipo_recinto',
    ];

    protected function casts(): array
    {
        return ['precio_por_hora' => 'decimal:2', 'estado' => 'string'];
    }

    public function horarios(): HasMany { return $this->hasMany(Horario::class); }
    public function reservas(): HasMany { return $this->hasMany(Reserva::class); }
    public function cierres(): HasMany { return $this->hasMany(CierreCancha::class); }

    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_DISPONIBLE);
    }

    public function estaDisponible(): bool
    {
        return $this->estado === self::ESTADO_DISPONIBLE;
    }

    // Consulta disponibilidad delegada a PoliticaReservasService
    public function horariosDisponiblesPara(string $fecha): Collection
    {
        return app(PoliticaReservasService::class)->horariosLibres($this, $fecha);
    }
}
```

---

## 6. `app/Rules/SinSuperposicionHorario.php` — Validation Rule

```php
<?php

namespace App\Rules;

use App\Models\Reserva;
use Illuminate\Contracts\Validation\Rule;

class SinSuperposicionHorario implements Rule
{
    public function __construct(
        private int $canchaId,
        private string $fecha,
        private string $horaInicio,
        private string $horaFin,
        private ?int $ignoreReservaId = null
    ) {}

    public function passes($attribute, $value): bool
    {
        $query = Reserva::query()
            ->activas()
            ->deCanchaEnFecha($this->canchaId, $this->fecha)
            ->where('hora_inicio_reserva', '<', $this->horaFin)
            ->where('hora_fin_reserva', '>', $this->horaInicio);

        if ($this->ignoreReservaId) {
            $query->where('id', '!=', $this->ignoreReservaId);
        }

        return ! $query->exists();
    }

    public function message(): string
    {
        return 'El horario seleccionado se superpone con otra reserva existente.';
    }
}
```

---

## 7. `app/Http/Controllers/ReservaController.php` — Cliente

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservaRequest;
use App\Models\Balon;
use App\Models\Cancha;
use App\Models\Horario;
use App\Services\ReservaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservaController extends Controller
{
    public function __construct(private readonly ReservaService $reservaService) {}

    public function crear(Request $request): View
    {
        $cancha = Cancha::disponibles()->findOrFail($request->integer('cancha_id'));
        $horario = $cancha->horarios()->findOrFail($request->integer('horario_id'));
        $fecha = $request->input('fecha');

        abort_if(! $this->reservaService->esFechaValida($fecha), 404);

        $balones = Balon::orderBy('tipo')->orderBy('marca')->orderBy('nombre')->get();
        $balonesOcupados = $this->reservaService->balonesOcupados($fecha, $horario);

        return view('reservas.crear', compact('cancha', 'horario', 'fecha', 'balones', 'balonesOcupados'));
    }

    public function store(StoreReservaRequest $request): RedirectResponse
    {
        $reserva = $this->reservaService->crearReserva($request->user(), $request->validated());
        return redirect()->route('reservas.mis')
            ->with('status', "Reserva #{$reserva->id} registrada correctamente.");
    }

    public function cancelar(Request $request, Reserva $reserva): RedirectResponse
    {
        $this->authorize('cancel', $reserva);
        abort_if(! $reserva->esCancelablePorCliente(), 422, 'Esta reserva ya no puede cancelarse.');
        
        $this->reservaService->cancelarReserva($reserva);
        return redirect()->route('reservas.mis')
            ->with('status', "Reserva #{$reserva->id} cancelada correctamente.");
    }
}
```

---

## 8. `app/Http/Controllers/Recepcionista/ReservaController.php` — Check-in/out

```php
<?php

namespace App\Http\Controllers\Recepcionista;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Services\ReservaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReservaController extends Controller
{
    public function __construct(private readonly ReservaService $reservaService) {}

    public function hoy(): View
    {
        $reservas = $this->reservaService->reservasConFiltros(estado: null, canchaId: null)
            ->where('fecha', now()->toDateString())
            ->get();
        return view('recepcionista.reservas.hoy', compact('reservas'));
    }

    public function checkIn(Reserva $reserva): RedirectResponse
    {
        $this->authorize('checkIn', $reserva);
        $this->reservaService->registrarCheckIn($reserva);
        return back()->with('status', 'Check-in registrado correctamente.');
    }

    public function checkOut(Reserva $reserva): RedirectResponse
    {
        $this->authorize('checkOut', $reserva);
        $this->reservaService->registrarCheckOut($reserva);
        return back()->with('status', 'Check-out registrado correctamente.');
    }
}
```

---

## 9. `app/Http/Requests/StoreReservaRequest.php`

```php
<?php

namespace App\Http\Requests;

use App\Rules\SinSuperposicionHorario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $canchaId = $this->integer('cancha_id');
        $horarioId = $this->integer('horario_id');
        $fecha = $this->input('fecha');
        $horario = \App\Models\Horario::find($horarioId);
        $horaFin = $this->input('hora_fin', $horario?->hora_fin);

        return [
            'cancha_id' => ['required', 'integer', 'exists:canchas,id'],
            'horario_id' => ['required', 'integer', 'exists:horarios,id'],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'balon_id' => ['nullable', 'integer', 'exists:balones,id'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            // Rule custom anti-solapamiento
            'horario_id' => [new SinSuperposicionHorario($canchaId, $fecha, $horario->hora_inicio, $horaFin)],
        ];
    }
}
```

---

## 10. `app/Http/Controllers/DisponibilidadController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalendarEventsRequest;
use App\Models\User;
use App\Services\ReservaCalendarService;
use Illuminate\Http\JsonResponse;

class DisponibilidadController extends Controller
{
    public function __construct(private readonly ReservaCalendarService $calendarService) {}

    public function index(): \Illuminate\View\View
    {
        return view('disponibilidad.index');
    }

    public function eventos(CalendarEventsRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $timezone = config('app.timezone', 'America/La_Paz');
        $inicio = \Carbon\Carbon::parse($datos['start'], $timezone)->startOfDay();
        $fin = \Carbon\Carbon::parse($datos['end'], $timezone)->startOfDay();

        $eventos = $this->calendarService->paraCliente(
            $request->user(), $inicio, $fin, $datos['estado'] ?? null
        );

        return response()->json($eventos)->withHeaders([
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
```

---

## 11. `app/Services/DisponibilidadService.php` — Fragmento clave

```php
<?php

namespace App\Services;

use App\Models\Cancha;
use App\Models\Horario;
use Illuminate\Database\Eloquent\Collection;

class DisponibilidadService
{
    public function obtenerDisponibilidad(string $fecha): array
    {
        $canchas = Cancha::disponibles()->with(['horarios' => fn ($q) => $q->where('disponible', true)])->get();
        
        return $canchas->map(function (Cancha $cancha) use ($fecha) {
            $horariosLibres = $cancha->horariosDisponiblesPara($fecha);
            $horariosOcupados = $this->horariosOcupados($cancha->id, $fecha);
            
            return [
                'cancha' => $cancha,
                'horarios_libres' => $horariosLibres,
                'horarios_ocupados' => $horariosOcupados,
            ];
        })->toArray();
    }
}
```

---

## 12. `app/Policies/ReservaPolicy.php` — Fragmento

```php
<?php

namespace App\Policies;

use App\Models\Reserva;
use App\Models\User;

class ReservaPolicy
{
    public function cancel(User $user, Reserva $reserva): bool
    {
        return $user->id === $reserva->user_id && $reserva->esCancelablePorCliente();
    }

    public function checkIn(User $user, Reserva $reserva): bool
    {
        return $user->esRecepcionista() && $reserva->estado === Reserva::ESTADO_CONFIRMADA;
    }

    public function checkOut(User $user, Reserva $reserva): bool
    {
        return $user->esRecepcionista() && $reserva->check_in_at !== null;
    }
}
```