<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcepcionReserva extends Model
{
    public const CIERRE_COMPLETO = 'cierre_completo';

    public const HORARIO_ESPECIAL = 'horario_especial';

    public const BLOQUEO_PARCIAL = 'bloqueo_parcial';

    public const MANTENIMIENTO = 'mantenimiento';

    public const FERIADO = 'feriado';

    public const VIAJE = 'viaje';

    public const EVENTO = 'evento';

    public const OTRO = 'otro';

    public const TIPOS = [
        self::CIERRE_COMPLETO, self::HORARIO_ESPECIAL, self::BLOQUEO_PARCIAL,
        self::MANTENIMIENTO, self::FERIADO, self::VIAJE, self::EVENTO, self::OTRO,
    ];

    public const ESTADO_BORRADOR = 'borrador';

    public const ESTADO_APLICADA = 'aplicada';

    public const ESTADO_CANCELADA = 'cancelada';

    protected $table = 'excepciones_reserva';

    protected $fillable = [
        'cancha_id', 'cambio_configuracion_id', 'creado_por', 'fecha', 'tipo',
        'hora_inicio', 'hora_fin', 'motivo', 'mensaje_cliente', 'estado', 'aplicado_at',
    ];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'aplicado_at' => 'datetime'];
    }

    public function cancha(): BelongsTo
    {
        return $this->belongsTo(Cancha::class);
    }

    public function cambio(): BelongsTo
    {
        return $this->belongsTo(CambioConfiguracionReserva::class, 'cambio_configuracion_id');
    }
}
