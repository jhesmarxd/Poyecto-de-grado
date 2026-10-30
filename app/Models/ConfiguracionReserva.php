<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ConfiguracionReserva extends Model
{
    protected $table = 'configuraciones_reserva';

    protected $fillable = [
        'anticipacion_maxima_dias', 'permitir_hoy', 'permitir_futuras',
        'duraciones_permitidas', 'minutos_franja', 'zona_horaria',
    ];

    protected function casts(): array
    {
        return [
            'anticipacion_maxima_dias' => 'integer',
            'permitir_hoy' => 'boolean',
            'permitir_futuras' => 'boolean',
            'duraciones_permitidas' => 'array',
            'minutos_franja' => 'integer',
        ];
    }

    public static function actual(): self
    {
        if (Schema::hasTable('configuraciones_reserva') && ($actual = static::query()->first())) {
            return $actual;
        }

        return new self([
            'anticipacion_maxima_dias' => 14,
            'permitir_hoy' => true,
            'permitir_futuras' => true,
            'duraciones_permitidas' => [60, 90, 120],
            'minutos_franja' => 30,
            'zona_horaria' => 'America/La_Paz',
        ]);
    }
}
