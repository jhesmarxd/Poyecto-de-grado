<?php

namespace App\Rules;

use App\Models\Horario;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

/**
 * Rechaza una franja horaria que se superponga con otro horario
 * ya registrado para la misma cancha y día de la semana.
 *
 * Dos intervalos [a1, a2) y [b1, b2) se superponen si:
 * a1 < b2 Y b1 < a2 (los extremos contiguos NO se consideran superpuestos).
 */
class SinSuperposicionHorario implements ValidationRule
{
    public function __construct(
        private readonly int $canchaId,
        private readonly int $diaSemana,
        private readonly ?string $horaInicio,
        private readonly ?string $horaFin,
        private readonly ?int $ignorarHorarioId = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->horaInicio || ! $this->horaFin) {
            return;
        }

        try {
            $inicio = Carbon::parse($this->horaInicio)->format('H:i:s');
            $fin = Carbon::parse($this->horaFin)->format('H:i:s');
        } catch (\Throwable) {
            return;
        }

        $seSuperpone = Horario::where('cancha_id', $this->canchaId)
            ->where('dia_semana', $this->diaSemana)
            ->when($this->ignorarHorarioId, fn ($q) => $q->where('id', '!=', $this->ignorarHorarioId))
            ->where('hora_inicio', '<', $fin)
            ->where('hora_fin', '>', $inicio)
            ->exists();

        if ($seSuperpone) {
            $fail('La franja horaria se superpone con otro horario ya registrado para ese día.');
        }
    }
}
