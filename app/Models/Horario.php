<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Horario extends Model
{
    use HasFactory;

    public const LUNES = 1;

    public const MARTES = 2;

    public const MIERCOLES = 3;

    public const JUEVES = 4;

    public const VIERNES = 5;

    public const SABADO = 6;

    public const DOMINGO = 7;

    /**
     * Nombres de los días de la semana (1 = Lunes ... 7 = Domingo).
     */
    public const DIAS = [
        self::LUNES => 'Lunes',
        self::MARTES => 'Martes',
        self::MIERCOLES => 'Miércoles',
        self::JUEVES => 'Jueves',
        self::VIERNES => 'Viernes',
        self::SABADO => 'Sábado',
        self::DOMINGO => 'Domingo',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'cancha_id',
        'dia_semana',
        'vigente_desde',
        'vigente_hasta',
        'hora_inicio',
        'hora_fin',
        'disponible',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
            'hora_inicio' => 'string',
            'hora_fin' => 'string',
            'disponible' => 'boolean',
        ];
    }

    /**
     * Cancha a la que pertenece el horario.
     */
    public function cancha(): BelongsTo
    {
        return $this->belongsTo(Cancha::class);
    }

    /**
     * Nombre legible del día de la semana.
     */
    public function nombreDia(): string
    {
        return self::DIAS[$this->dia_semana] ?? "Día {$this->dia_semana}";
    }

    /**
     * Reservas registradas sobre esta franja horaria.
     */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }
}
