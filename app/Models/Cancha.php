<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Cancha extends Model
{
    use HasFactory;

    public const ESTADO_DISPONIBLE = 'disponible';

    public const ESTADO_MANTENIMIENTO = 'mantenimiento';

    public const ESTADO_INACTIVA = 'inactiva';

    public const ESTADOS = [
        self::ESTADO_DISPONIBLE => 'Disponible',
        self::ESTADO_MANTENIMIENTO => 'En mantenimiento',
        self::ESTADO_INACTIVA => 'Inactiva',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'ubicacion',
        'capacidad',
        'precio_por_hora',
        'estado',
        'imagen',
        'color_malla',
        'altura_malla',
        'tipo_piso',
        'tipo_recinto',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacidad' => 'integer',
            'precio_por_hora' => 'decimal:2',
            'estado' => 'string',
        ];
    }

    /**
     * Horarios de funcionamiento de la cancha.
     */
    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class);
    }

    /**
     * Reservas registradas sobre la cancha.
     */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function cierres(): HasMany
    {
        return $this->hasMany(CierreCancha::class);
    }

    /**
     * Canchas disponibles para reserva.
     */
    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_DISPONIBLE);
    }

    /**
     * Determina si la cancha está disponible para reservas.
     */
    public function estaDisponible(): bool
    {
        return $this->estado === self::ESTADO_DISPONIBLE;
    }

    /**
     * Franjas horarias libres de la cancha para una fecha concreta.
     *
     * Devuelve los horarios de la cancha para el día de la semana de la fecha
     * que estén habilitados y que no tengan una reserva activa (no cancelada)
     * registrada para esa fecha.
     */
    public function horariosDisponiblesPara(string $fecha): Collection
    {
        if ($this->cierres()->whereDate('fecha', $fecha)->exists()) {
            return new Collection;
        }

        $diaSemana = Carbon::parse($fecha)->dayOfWeekIso; // 1 = Lunes ... 7 = Domingo

        return $this->horarios()
            ->where('dia_semana', $diaSemana)
            ->where('disponible', true)
            ->where(fn (Builder $query) => $query
                ->whereNull('vigente_desde')
                ->orWhereDate('vigente_desde', '<=', $fecha))
            ->where(fn (Builder $query) => $query
                ->whereNull('vigente_hasta')
                ->orWhereDate('vigente_hasta', '>=', $fecha))
            ->orderBy('hora_inicio')
            ->get()
            ->reject(function (Horario $horario) use ($fecha): bool {
                return Reserva::query()
                    ->activas()
                    ->deCanchaEnFecha($this->id, $fecha)
                    ->where('hora_inicio_reserva', '<', $horario->hora_fin)
                    ->where('hora_fin_reserva', '>', $horario->hora_inicio)
                    ->exists();
            })
            ->values();
    }

    /**
     * URL pública de la imagen de la cancha (o null si no tiene).
     */
    public function imagenUrl(?string $version = null): ?string
    {
        if (! $this->imagen) {
            return null;
        }

        return asset('img/canchas/'.$this->imagen).($version ? '?v='.$version : '');
    }

    /**
     * Indica si la cancha es cerrada (techada, no campo abierto).
     */
    public function esCerrada(): bool
    {
        return $this->tipo_recinto === 'cerrada';
    }
}
