<?php

namespace App\Services;

use App\Models\Cancha;

class DisponibilidadService
{
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

        return $cancha->horariosDisponiblesPara($fecha);
    }

    /**
     * Valida que la fecha sea válida (formato Y-m-d).
     */
    public function esFechaValida(?string $fecha): bool
    {
        return $fecha !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) === 1;
    }
}
