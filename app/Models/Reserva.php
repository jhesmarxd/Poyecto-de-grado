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

    protected static function booted(): void
    {
        static::creating(function (Reserva $reserva): void {
            if ($reserva->hora_inicio_reserva && $reserva->hora_fin_reserva) {
                return;
            }

            $horario = Horario::find($reserva->horario_id);
            if (! $horario) {
                return;
            }

            $reserva->hora_inicio_reserva = $horario->hora_inicio;
            $reserva->hora_fin_reserva = $horario->hora_fin;
            $inicio = Carbon::parse($horario->hora_inicio);
            $fin = Carbon::parse($horario->hora_fin);
            if ($fin->lessThanOrEqualTo($inicio)) {
                $fin->addDay();
            }
            $reserva->duracion_minutos = $inicio->diffInMinutes($fin);
        });
    }

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_CONFIRMADA = 'confirmada';

    public const ESTADO_CANCELADA = 'cancelada';

    public const ESTADO_COMPLETADA = 'completada';

    public const ESTADO_AFECTADA = 'afectada_por_configuracion';

    public const ESTADO_CANCELADA_COMPLEJO = 'cancelada_por_complejo';

    public const ESTADO_REPROGRAMADA = 'reprogramada';

    /**
     * Tipos de balón que puede elegir el cliente en una reserva.
     */
    public const BALON_PROPIO = 'propio';

    public const BALON_PRESTADO = 'prestado';

    public const BALON_PREMIUM = 'premium';

    public const OPCIONES_BALON = [
        self::BALON_PROPIO => 'Balón propio (traes tu balón)',
        self::BALON_PRESTADO => 'Balón prestado (económico)',
        self::BALON_PREMIUM => 'Balón premium',
    ];

    /**
     * Etiquetas cortas para mostrar en tablas.
     */
    public const OPCIONES_BALON_CORTAS = [
        self::BALON_PROPIO => 'Propio',
        self::BALON_PRESTADO => 'Prestado',
        self::BALON_PREMIUM => 'Premium',
    ];

    /**
     * Estados de la reserva con su etiqueta en español.
     */
    public const ESTADOS = [
        self::ESTADO_PENDIENTE => 'Pendiente',
        self::ESTADO_CONFIRMADA => 'Confirmada',
        self::ESTADO_CANCELADA => 'Cancelada',
        self::ESTADO_COMPLETADA => 'Completada',
        self::ESTADO_AFECTADA => 'Afectada por configuración',
        self::ESTADO_CANCELADA_COMPLEJO => 'Cancelada por el complejo',
        self::ESTADO_REPROGRAMADA => 'Reprogramada',
    ];

    public const ESTADOS_GESTIONABLES = [
        self::ESTADO_PENDIENTE => 'Pendiente',
        self::ESTADO_CONFIRMADA => 'Confirmada',
        self::ESTADO_CANCELADA => 'Cancelada',
        self::ESTADO_COMPLETADA => 'Completada',
    ];

    /**
     * Clases de badge (Bootstrap) por estado.
     */
    public const CLASES_BADGE = [
        self::ESTADO_PENDIENTE => 'bg-warning text-dark',
        self::ESTADO_CONFIRMADA => 'bg-success',
        self::ESTADO_CANCELADA => 'bg-danger',
        self::ESTADO_COMPLETADA => 'bg-secondary',
        self::ESTADO_AFECTADA => 'bg-warning text-dark',
        self::ESTADO_CANCELADA_COMPLEJO => 'bg-danger',
        self::ESTADO_REPROGRAMADA => 'bg-info text-dark',
    ];

    /**
     * Colores para FullCalendar por estado.
     */
    public const COLORES = [
        self::ESTADO_PENDIENTE => '#ffc107',
        self::ESTADO_CONFIRMADA => '#198754',
        self::ESTADO_CANCELADA => '#dc3545',
        self::ESTADO_COMPLETADA => '#6c757d',
        self::ESTADO_AFECTADA => '#fd7e14',
        self::ESTADO_CANCELADA_COMPLEJO => '#dc3545',
        self::ESTADO_REPROGRAMADA => '#0dcaf0',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'reprogramada_desde_id',
        'cancha_id',
        'horario_id',
        'fecha',
        'hora_inicio_reserva',
        'hora_fin_reserva',
        'duracion_minutos',
        'monto_total',
        'tipo_balon',
        'balon_id',
        'estado',
        'observaciones',
        'check_in_at',
        'check_out_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
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

    /**
     * Indica si el cliente se encuentra usando la cancha.
     */
    public function estaEnCurso(): bool
    {
        return $this->check_in_at !== null && $this->check_out_at === null;
    }

    /**
     * Fecha y hora programadas de inicio en la zona horaria del sistema.
     */
    public function iniciaEn(): Carbon
    {
        return Carbon::parse(
            $this->fecha->format('Y-m-d').' '.($this->hora_inicio_reserva ?: $this->horario->hora_inicio),
            (string) config('app.timezone', 'UTC')
        );
    }

    /**
     * Fecha y hora programadas de finalización en la zona horaria del sistema.
     */
    public function finalizaEn(): Carbon
    {
        $inicio = $this->iniciaEn();
        $fin = Carbon::parse(
            $this->fecha->format('Y-m-d').' '.($this->hora_fin_reserva ?: $this->horario->hora_fin),
            (string) config('app.timezone', 'UTC')
        );

        // También admite una eventual franja que cruce la medianoche.
        return $fin->lessThanOrEqualTo($inicio) ? $fin->addDay() : $fin;
    }

    /**
     * Estado operativo mostrado en la agenda de recepción.
     */
    public function estadoOperacionLabel(): string
    {
        if ($this->check_out_at !== null
            || ($this->estado === self::ESTADO_COMPLETADA && $this->fecha->isPast())) {
            return 'Finalizada';
        }

        if ($this->estaEnCurso()) {
            return 'En curso';
        }

        return $this->estadoLabel();
    }

    /**
     * Usuario que realiza la reserva.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Cancha reservada.
     */
    public function cancha(): BelongsTo
    {
        return $this->belongsTo(Cancha::class);
    }

    /**
     * Franja horaria reservada.
     */
    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class);
    }

    /**
     * Balón concreto elegido para la reserva (null = propio).
     */
    public function balon(): BelongsTo
    {
        return $this->belongsTo(Balon::class);
    }

    /**
     * Enlace del evento sincronizado con Google Calendar.
     */
    public function googleCalendarEvent(): HasOne
    {
        return $this->hasOne(GoogleCalendarEvent::class);
    }

    public function afectacionConfiguracion(): HasOne
    {
        return $this->hasOne(ReservaAfectadaConfiguracion::class)->latestOfMany();
    }

    public function reservaOriginal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reprogramada_desde_id');
    }

    /**
     * Reservas que aún están vigentes (no canceladas).
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->whereNotIn('estado', [
            self::ESTADO_CANCELADA,
            self::ESTADO_CANCELADA_COMPLEJO,
            self::ESTADO_REPROGRAMADA,
        ]);
    }

    /**
     * Reservas para una fecha determinada.
     */
    public function scopeParaFecha(Builder $query, string $fecha): Builder
    {
        return $query->where('fecha', $fecha);
    }

    /**
     * Reservas de una cancha para una fecha determinada.
     */
    public function scopeDeCanchaEnFecha(Builder $query, int $canchaId, string $fecha): Builder
    {
        return $query->where('cancha_id', $canchaId)->whereDate('fecha', $fecha);
    }

    /**
     * Determina si la reserva está cancelada.
     */
    public function estaCancelada(): bool
    {
        return in_array($this->estado, [
            self::ESTADO_CANCELADA,
            self::ESTADO_CANCELADA_COMPLEJO,
            self::ESTADO_REPROGRAMADA,
        ], true);
    }

    /**
     * Determina si el cliente puede cancelar esta reserva
     * (pendiente o confirmada y con fecha futura).
     */
    public function esCancelablePorCliente(): bool
    {
        return in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_CONFIRMADA], true)
            && $this->fecha->isAfter(Carbon::today());
    }

    /**
     * Etiqueta legible del estado.
     */
    public function estadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    /**
     * Clase de badge para el estado.
     */
    public function estadoBadge(): string
    {
        return self::CLASES_BADGE[$this->estado] ?? 'bg-secondary';
    }

    /**
     * Etiqueta legible del tipo de balón elegido.
     */
    public function balonLabel(): string
    {
        if ($this->balon) {
            return $this->balon->tipo === Balon::TIPO_PREMIUM
                ? 'Balón premium · '.$this->balon->marcaModelo()
                : 'Balón prestado · '.$this->balon->marcaModelo();
        }

        return self::OPCIONES_BALON[$this->tipo_balon] ?? ($this->tipo_balon ?? '—');
    }

    /**
     * Etiqueta corta del tipo de balón elegido.
     */
    public function balonLabelCorta(): string
    {
        if ($this->balon) {
            return ($this->balon->tipo === Balon::TIPO_PREMIUM ? 'Premium' : 'Prestado')
                .' · '.$this->balon->marcaModelo();
        }

        return self::OPCIONES_BALON_CORTAS[$this->tipo_balon] ?? ($this->tipo_balon ?? '—');
    }

    /**
     * Determina el monto final de una reserva según el balón elegido:
     * si se elige un balón del catálogo se cobra su precio por hora;
     * si el cliente trae su propio balón se cobra la tarifa base de la cancha.
     */
    public static function calcularMonto(float $precioBaseCancha, ?int $balonId): float
    {
        if ($balonId) {
            $balon = Balon::find($balonId);

            return $balon ? (float) $balon->precio_por_hora : $precioBaseCancha;
        }

        return $precioBaseCancha;
    }

    /**
     * Duración facturable de la reserva expresada en horas.
     */
    public function duracionHoras(): float
    {
        $minutos = $this->duracion_minutos ?: $this->iniciaEn()->diffInMinutes($this->finalizaEn());

        return max(1, $minutos) / 60;
    }
}
