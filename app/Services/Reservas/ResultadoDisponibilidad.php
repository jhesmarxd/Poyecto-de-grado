<?php

namespace App\Services\Reservas;

use App\Models\Cancha;
use Illuminate\Support\Collection;

final class ResultadoDisponibilidad
{
    public const DISPONIBLE = 'disponible';

    public const UNA_CANCHA = 'una_cancha';

    public const PARCIAL = 'parcial';

    public const SIN_DISPONIBILIDAD = 'sin_disponibilidad';

    public const FECHA_INVALIDA = 'fecha_invalida';

    public const FECHA_PASADA = 'fecha_pasada';

    public const FUERA_DE_RANGO = 'fuera_de_rango';

    public const DIA_CERRADO = 'dia_cerrado';

    public const FUERA_DE_HORARIO = 'fuera_de_horario';

    public const CANCHA_NO_HABILITADA = 'cancha_no_habilitada';

    public const BLOQUEADO = 'bloqueado';

    public const HORARIO_PASADO = 'horario_pasado';

    public const DATOS_INSUFICIENTES = 'datos_insuficientes';

    public const HORARIO_AMBIGUO = 'horario_ambiguo';

    /**
     * @param  Collection<int, Cancha>  $canchas
     * @param  array<int, array{inicio: string, fin: string, cancha_id?: int}>  $alternativas
     */
    public function __construct(
        public readonly string $estado,
        public readonly string $mensaje,
        public readonly Collection $canchas = new Collection,
        public readonly array $alternativas = [],
        public readonly ?string $fecha = null,
        public readonly ?string $horaInicio = null,
        public readonly ?string $horaFin = null,
    ) {}

    public function disponible(): bool
    {
        return in_array($this->estado, [self::DISPONIBLE, self::UNA_CANCHA], true)
            && $this->canchas->isNotEmpty();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'estado' => $this->estado,
            'fecha' => $this->fecha,
            'hora_inicio' => $this->horaInicio,
            'hora_fin' => $this->horaFin,
            'cancha_ids' => $this->canchas->pluck('id')->values()->all(),
            'alternativas' => $this->alternativas,
        ];
    }
}
