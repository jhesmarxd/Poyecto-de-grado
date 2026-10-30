<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservaAfectadaConfiguracion extends Model
{
    protected $table = 'reservas_afectadas_configuracion';

    protected $fillable = [
        'cambio_configuracion_id', 'excepcion_reserva_id', 'reserva_id',
        'estado_anterior', 'estado_nuevo', 'mensaje', 'estado_notificacion',
        'notificado_at', 'resuelto_at',
    ];

    protected function casts(): array
    {
        return ['notificado_at' => 'datetime', 'resuelto_at' => 'datetime'];
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    public function cambio(): BelongsTo
    {
        return $this->belongsTo(CambioConfiguracionReserva::class, 'cambio_configuracion_id');
    }

    public function excepcion(): BelongsTo
    {
        return $this->belongsTo(ExcepcionReserva::class, 'excepcion_reserva_id');
    }
}
