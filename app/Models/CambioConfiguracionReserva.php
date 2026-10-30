<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CambioConfiguracionReserva extends Model
{
    public const ESTADO_PENDIENTE = 'pendiente_revision';

    public const ESTADO_APLICADO = 'aplicado';

    public const ESTADO_CANCELADO = 'cancelado';

    protected $table = 'cambios_configuracion_reserva';

    protected $fillable = [
        'administrador_id', 'tipo', 'valor_anterior', 'valor_nuevo',
        'motivo', 'estado', 'aplicado_at',
    ];

    protected function casts(): array
    {
        return ['valor_anterior' => 'array', 'valor_nuevo' => 'array', 'aplicado_at' => 'datetime'];
    }

    public function administrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administrador_id');
    }

    public function excepciones(): HasMany
    {
        return $this->hasMany(ExcepcionReserva::class, 'cambio_configuracion_id');
    }

    public function reservasAfectadas(): HasMany
    {
        return $this->hasMany(ReservaAfectadaConfiguracion::class, 'cambio_configuracion_id');
    }
}
