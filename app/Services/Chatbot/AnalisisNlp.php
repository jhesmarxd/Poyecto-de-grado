<?php

namespace App\Services\Chatbot;

use DateTimeImmutable;

final readonly class AnalisisNlp
{
    public function __construct(
        public string $intencion,
        public ?int $reservaId = null,
        public ?string $canchaReferencia = null,
        public ?string $fecha = null,
        public ?string $horaInicio = null,
        public ?string $horaFin = null,
        public ?int $duracionMinutos = null,
        public ?string $balonReferencia = null,
    ) {}

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'intencion' => $this->intencion,
            'reserva_id' => $this->reservaId,
            'cancha_referencia' => $this->canchaReferencia,
            'fecha' => $this->fecha,
            'hora_inicio' => $this->horaInicio,
            'hora_fin' => $this->horaFin,
            'duracion_minutos' => $this->duracionMinutos,
            'balon_referencia' => $this->balonReferencia,
        ];
    }

    /** @param array<string, mixed> $datos */
    public static function desdeArray(array $datos): ?self
    {
        $intencion = self::texto($datos['intencion'] ?? null, 60);
        if (! $intencion || ! in_array($intencion, DetectorIntencion::catalogo(), true)) {
            return null;
        }

        $reservaId = filter_var($datos['reserva_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $reservaId = $reservaId === false ? null : $reservaId;

        $duracion = filter_var($datos['duracion_minutos'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 15, 'max_range' => 720],
        ]);
        $duracion = $duracion === false ? null : $duracion;
        $horaInicio = self::hora($datos['hora_inicio'] ?? null);
        $horaFin = self::hora($datos['hora_fin'] ?? null);

        if ($horaInicio && $horaFin && $horaFin <= $horaInicio) {
            $horaFin = null;
        }

        return new self(
            intencion: $intencion,
            reservaId: $reservaId,
            canchaReferencia: self::texto($datos['cancha_referencia'] ?? null, 120),
            fecha: self::fecha($datos['fecha'] ?? null),
            horaInicio: $horaInicio,
            horaFin: $horaFin,
            duracionMinutos: $duracion,
            balonReferencia: self::texto($datos['balon_referencia'] ?? null, 120),
        );
    }

    private static function texto(mixed $valor, int $maximo): ?string
    {
        if (! is_string($valor)) {
            return null;
        }

        $valor = trim($valor);

        return $valor === '' ? null : mb_substr($valor, 0, $maximo);
    }

    private static function fecha(mixed $valor): ?string
    {
        $valor = self::texto($valor, 10);
        if (! $valor) {
            return null;
        }

        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        $errores = DateTimeImmutable::getLastErrors();

        return $fecha
            && ($errores === false || ($errores['warning_count'] === 0 && $errores['error_count'] === 0))
            && $fecha->format('Y-m-d') === $valor
                ? $valor
                : null;
    }

    private static function hora(mixed $valor): ?string
    {
        if (! is_string($valor) || ! preg_match('/^(\d{2}):(\d{2})(?::\d{2})?$/', trim($valor), $partes)) {
            return null;
        }

        $hora = (int) $partes[1];
        $minuto = (int) $partes[2];

        return $hora <= 23 && $minuto <= 59
            ? sprintf('%02d:%02d:00', $hora, $minuto)
            : null;
    }
}
