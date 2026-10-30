<?php

namespace App\Services;

use App\Models\Cancha;

class DisponibilidadService
{
    public function __construct(
        private readonly PoliticaReservasService $politica,
    ) {}

    /**
     * Obtiene las canchas disponibles ordenadas por nombre.
     */
    public function canchasDisponibles()
    {
        return Cancha::disponibles()->orderBy('nombre')->get();
    }

    /**
     * Obtiene los horarios libres para una cancha y fecha específicas.
     */
    public function horariosLibres(Cancha $cancha, string $fecha)
    {
        if (! $this->esFechaValida($fecha)) {
            return collect();
        }

        return $this->politica->horariosLibres($cancha, $fecha);
    }

    /**
     * Valida que la fecha sea válida (formato Y-m-d).
     */
    public function esFechaValida(?string $fecha): bool
    {
        return $this->politica->fechaValida($fecha);
    }
}
