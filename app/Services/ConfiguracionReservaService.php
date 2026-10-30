<?php

namespace App\Services;

use App\Models\Ajuste;
use App\Models\CambioConfiguracionReserva;
use App\Models\Cancha;
use App\Models\ConfiguracionReserva;
use App\Models\ExcepcionReserva;
use App\Models\HorarioSemanal;
use App\Models\Reserva;
use App\Models\ReservaAfectadaConfiguracion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ConfiguracionReservaService
{
    public function __construct(
        private readonly ReservaNotificationService $notificaciones,
        private readonly ReservaService $reservas,
    ) {}

    /** @param array<string, mixed> $datos */
    public function prepararConfiguracion(array $datos, User $administrador): CambioConfiguracionReserva
    {
        $anterior = $this->snapshot();
        $nuevo = [
            'anticipacion_maxima_dias' => (int) $datos['anticipacion_maxima_dias'],
            'permitir_hoy' => (bool) ($datos['permitir_hoy'] ?? false),
            'permitir_futuras' => (bool) ($datos['permitir_futuras'] ?? false),
            'duraciones_permitidas' => array_values(array_map('intval', $datos['duraciones_permitidas'])),
            'minutos_franja' => 30,
            'zona_horaria' => 'America/La_Paz',
            'horarios' => $this->normalizarHorarios($datos['horarios']),
        ];

        $cambio = CambioConfiguracionReserva::create([
            'administrador_id' => $administrador->id,
            'tipo' => 'configuracion_general',
            'valor_anterior' => $anterior,
            'valor_nuevo' => $nuevo,
            'motivo' => $datos['motivo'] ?? 'Actualización de reglas de reserva',
            'estado' => CambioConfiguracionReserva::ESTADO_PENDIENTE,
        ]);

        $this->registrarAfectadas($cambio, $this->reservasAfectadasPorHorario($nuevo['horarios']));

        return $cambio->load('reservasAfectadas.reserva.usuario', 'reservasAfectadas.reserva.cancha');
    }

    /** @param array<string, mixed> $datos */
    public function prepararExcepcion(array $datos, User $administrador): CambioConfiguracionReserva
    {
        return DB::transaction(function () use ($datos, $administrador): CambioConfiguracionReserva {
            $payload = [
                'cancha_id' => filled($datos['cancha_id'] ?? null) ? (int) $datos['cancha_id'] : null,
                'fecha' => $datos['fecha'],
                'tipo' => $datos['tipo'],
                'hora_inicio' => $datos['hora_inicio'] ?? null,
                'hora_fin' => $datos['hora_fin'] ?? null,
                'motivo' => $datos['motivo'],
                'mensaje_cliente' => $datos['mensaje_cliente'] ?? null,
            ];
            $cambio = CambioConfiguracionReserva::create([
                'administrador_id' => $administrador->id,
                'tipo' => 'excepcion',
                'valor_nuevo' => $payload,
                'motivo' => $payload['motivo'],
                'estado' => CambioConfiguracionReserva::ESTADO_PENDIENTE,
            ]);
            $excepcion = ExcepcionReserva::create($payload + [
                'cambio_configuracion_id' => $cambio->id,
                'creado_por' => $administrador->id,
                'estado' => ExcepcionReserva::ESTADO_BORRADOR,
            ]);

            $this->registrarAfectadas($cambio, $this->reservasAfectadasPorExcepcion($excepcion), $excepcion);

            return $cambio->load('excepciones', 'reservasAfectadas.reserva.usuario', 'reservasAfectadas.reserva.cancha');
        });
    }

    public function aplicar(CambioConfiguracionReserva $cambio): CambioConfiguracionReserva
    {
        return DB::transaction(function () use ($cambio): CambioConfiguracionReserva {
            $cambio = CambioConfiguracionReserva::query()->lockForUpdate()->findOrFail($cambio->id);
            if ($cambio->estado === CambioConfiguracionReserva::ESTADO_APLICADO) {
                return $cambio;
            }
            if ($cambio->estado !== CambioConfiguracionReserva::ESTADO_PENDIENTE) {
                throw new HttpException(422, 'El cambio ya no puede aplicarse.');
            }

            if ($cambio->tipo === 'excepcion') {
                $excepcion = $cambio->excepciones()->lockForUpdate()->firstOrFail();
                Cancha::query()
                    ->when($excepcion->cancha_id, fn ($query) => $query->whereKey($excepcion->cancha_id))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                $afectadas = $this->reservasAfectadasPorExcepcion($excepcion, true);
                $this->registrarAfectadas($cambio, $afectadas, $excepcion);
                $excepcion->update(['estado' => ExcepcionReserva::ESTADO_APLICADA, 'aplicado_at' => now()]);
            } else {
                Cancha::query()->orderBy('id')->lockForUpdate()->get();
                $nuevo = $cambio->valor_nuevo;
                $afectadas = $this->reservasAfectadasPorHorario($nuevo['horarios'], true);
                $this->registrarAfectadas($cambio, $afectadas);
                $this->guardarConfiguracion($nuevo);
            }

            $cambio->load('reservasAfectadas.reserva.usuario', 'reservasAfectadas.reserva.cancha');
            foreach ($cambio->reservasAfectadas as $afectada) {
                $reserva = Reserva::query()->lockForUpdate()->findOrFail($afectada->reserva_id);
                if (! in_array($reserva->estado, [Reserva::ESTADO_PENDIENTE, Reserva::ESTADO_CONFIRMADA], true)) {
                    continue;
                }
                $mensaje = $afectada->mensaje ?: $this->mensajeAfectacion($reserva, $cambio);
                $notificada = $this->notificaciones->enviarAfectada($reserva, $cambio, $mensaje);
                if (! $notificada) {
                    throw new HttpException(
                        422,
                        'No se pudo crear la notificación interna de la reserva #'.$reserva->id.'. No se aplicó ningún cambio.'
                    );
                }
                $this->reservas->marcarAfectadaPorConfiguracion($reserva);
                $afectada->update([
                    'estado_nuevo' => Reserva::ESTADO_AFECTADA,
                    'estado_notificacion' => 'notificada',
                    'notificado_at' => now(),
                    'mensaje' => $mensaje,
                ]);
            }

            $cambio->update(['estado' => CambioConfiguracionReserva::ESTADO_APLICADO, 'aplicado_at' => now()]);

            return $cambio->fresh(['reservasAfectadas.reserva.usuario', 'reservasAfectadas.reserva.cancha', 'excepciones']);
        });
    }

    public function cancelarAfectada(ReservaAfectadaConfiguracion $afectada): void
    {
        DB::transaction(function () use ($afectada): void {
            $afectada = ReservaAfectadaConfiguracion::query()->lockForUpdate()->findOrFail($afectada->id);
            $reserva = Reserva::query()->lockForUpdate()->findOrFail($afectada->reserva_id);
            abort_unless($reserva->estado === Reserva::ESTADO_AFECTADA, 422, 'La reserva ya fue resuelta.');
            $this->reservas->cancelarPorComplejo($reserva);
            $afectada->update(['estado_nuevo' => Reserva::ESTADO_CANCELADA_COMPLEJO, 'resuelto_at' => now()]);
        });
    }

    /** @param array{cancha_id:int, horario_id:int, fecha:string, hora_fin:string} $datos */
    public function reprogramar(ReservaAfectadaConfiguracion $afectada, array $datos): Reserva
    {
        return DB::transaction(function () use ($afectada, $datos): Reserva {
            $afectada = ReservaAfectadaConfiguracion::query()->lockForUpdate()->findOrFail($afectada->id);
            $original = Reserva::query()->lockForUpdate()->findOrFail($afectada->reserva_id);
            abort_unless($original->estado === Reserva::ESTADO_AFECTADA, 422, 'La reserva ya fue resuelta.');

            $nueva = $this->reservas->crearReserva($original->usuario, $datos + [
                'balon_id' => $original->balon_id,
                'observaciones' => 'Reprogramada desde la reserva #'.$original->id,
                'reprogramada_desde_id' => $original->id,
            ]);
            if ($afectada->estado_anterior === Reserva::ESTADO_CONFIRMADA) {
                $this->reservas->cambiarEstado($nueva, Reserva::ESTADO_CONFIRMADA);
            }
            $this->reservas->marcarReprogramada($original);
            $afectada->update(['estado_nuevo' => Reserva::ESTADO_REPROGRAMADA, 'resuelto_at' => now()]);

            return $nueva;
        });
    }

    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        $config = ConfiguracionReserva::actual();

        return [
            'anticipacion_maxima_dias' => $config->anticipacion_maxima_dias,
            'permitir_hoy' => $config->permitir_hoy,
            'permitir_futuras' => $config->permitir_futuras,
            'duraciones_permitidas' => $config->duraciones_permitidas,
            'minutos_franja' => $config->minutos_franja,
            'zona_horaria' => $config->zona_horaria,
            'horarios' => HorarioSemanal::query()->orderBy('dia_semana')->get()->keyBy('dia_semana')->map(fn ($h) => [
                'abierto' => $h->abierto,
                'hora_apertura' => $h->hora_apertura ? substr($h->hora_apertura, 0, 5) : null,
                'hora_cierre' => $h->hora_cierre ? substr($h->hora_cierre, 0, 5) : null,
            ])->all(),
        ];
    }

    /** @param array<int|string, array<string, mixed>> $horarios */
    private function normalizarHorarios(array $horarios): array
    {
        $normalizados = [];
        foreach (range(1, 7) as $dia) {
            $valor = $horarios[$dia] ?? [];
            $abierto = (bool) ($valor['abierto'] ?? false);
            $normalizados[$dia] = [
                'abierto' => $abierto,
                'hora_apertura' => $abierto ? $valor['hora_apertura'] : null,
                'hora_cierre' => $abierto ? $valor['hora_cierre'] : null,
            ];
        }

        return $normalizados;
    }

    /** @param array<string, mixed> $nuevo */
    private function guardarConfiguracion(array $nuevo): void
    {
        $config = ConfiguracionReserva::query()->first() ?? new ConfiguracionReserva;
        $config->fill(collect($nuevo)->except('horarios')->all())->save();
        foreach ($nuevo['horarios'] as $dia => $horario) {
            HorarioSemanal::updateOrCreate(['dia_semana' => $dia], $horario);
        }
        $this->sincronizarHorarioInformativo($nuevo['horarios']);
        $this->materializarFranjas($nuevo['horarios'], (int) $nuevo['minutos_franja']);
    }

    /**
     * Mantiene las claves antiguas como espejo para integraciones existentes.
     * La fuente de verdad sigue siendo horarios_semanales.
     *
     * @param  array<int|string, array<string, mixed>>  $horarios
     */
    private function sincronizarHorarioInformativo(array $horarios): void
    {
        $abiertos = collect($horarios)->filter(fn (array $horario): bool => (bool) $horario['abierto']);
        if ($abiertos->isEmpty()) {
            return;
        }

        Ajuste::guardarHorarioAtencion([
            'horario_atencion_dia_inicio' => (int) $abiertos->keys()->min(),
            'horario_atencion_dia_fin' => (int) $abiertos->keys()->max(),
            'horario_atencion_hora_inicio' => (string) $abiertos->min('hora_apertura'),
            'horario_atencion_hora_fin' => (string) $abiertos->max('hora_cierre'),
        ]);
    }

    /** @param array<int|string, array<string, mixed>> $horarios */
    private function materializarFranjas(array $horarios, int $minutos): void
    {
        foreach (Cancha::query()->get() as $cancha) {
            foreach ($horarios as $dia => $regla) {
                $iniciosActivos = [];
                if ($regla['abierto']) {
                    $cursor = Carbon::createFromFormat('H:i', $regla['hora_apertura']);
                    $fin = Carbon::createFromFormat('H:i', $regla['hora_cierre']);
                    while ($cursor->lt($fin)) {
                        $horaInicio = $cursor->format('H:i:s');
                        $horaFin = $cursor->copy()->addMinutes($minutos)->format('H:i:s');
                        $iniciosActivos[] = $horaInicio;
                        $cancha->horarios()->updateOrCreate(
                            ['dia_semana' => $dia, 'hora_inicio' => $horaInicio],
                            ['hora_fin' => $horaFin, 'vigente_desde' => null, 'vigente_hasta' => null, 'disponible' => true]
                        );
                        $cursor->addMinutes($minutos);
                    }
                }
                $cancha->horarios()->where('dia_semana', $dia)
                    ->when($iniciosActivos, fn ($query) => $query->whereNotIn('hora_inicio', $iniciosActivos))
                    ->when(! $iniciosActivos, fn ($query) => $query)
                    ->update(['disponible' => false]);
            }
        }
    }

    /** @param array<int|string, array<string, mixed>> $horarios */
    private function reservasAfectadasPorHorario(array $horarios, bool $bloquear = false): Collection
    {
        $query = Reserva::query()
            ->whereIn('estado', [Reserva::ESTADO_PENDIENTE, Reserva::ESTADO_CONFIRMADA])
            ->whereDate('fecha', '>=', today());
        if ($bloquear) {
            $query->lockForUpdate();
        }

        return $query->get()->filter(function (Reserva $reserva) use ($horarios): bool {
            $regla = $horarios[$reserva->fecha->dayOfWeekIso] ?? null;
            if (! $regla || ! $regla['abierto']) {
                return true;
            }
            $inicio = $reserva->hora_inicio_reserva ?: $reserva->horario?->hora_inicio;
            $fin = $reserva->hora_fin_reserva ?: $reserva->horario?->hora_fin;

            return $inicio < $regla['hora_apertura'].':00' || $fin > $regla['hora_cierre'].':00';
        })->values();
    }

    private function reservasAfectadasPorExcepcion(ExcepcionReserva $excepcion, bool $bloquear = false): Collection
    {
        $query = Reserva::query()
            ->whereIn('estado', [Reserva::ESTADO_PENDIENTE, Reserva::ESTADO_CONFIRMADA])
            ->whereDate('fecha', $excepcion->fecha)
            ->when($excepcion->cancha_id, fn ($q) => $q->where('cancha_id', $excepcion->cancha_id));
        if ($bloquear) {
            $query->lockForUpdate();
        }

        return $query->get()->filter(function (Reserva $reserva) use ($excepcion): bool {
            if ($excepcion->tipo === ExcepcionReserva::HORARIO_ESPECIAL) {
                return $reserva->hora_inicio_reserva < $excepcion->hora_inicio
                    || $reserva->hora_fin_reserva > $excepcion->hora_fin;
            }
            if (! $excepcion->hora_inicio || ! $excepcion->hora_fin) {
                return true;
            }

            return $reserva->hora_inicio_reserva < $excepcion->hora_fin
                && $reserva->hora_fin_reserva > $excepcion->hora_inicio;
        })->values();
    }

    private function registrarAfectadas(
        CambioConfiguracionReserva $cambio,
        Collection $reservas,
        ?ExcepcionReserva $excepcion = null,
    ): void {
        foreach ($reservas as $reserva) {
            ReservaAfectadaConfiguracion::firstOrCreate(
                ['cambio_configuracion_id' => $cambio->id, 'reserva_id' => $reserva->id],
                [
                    'excepcion_reserva_id' => $excepcion?->id,
                    'estado_anterior' => $reserva->estado,
                    'mensaje' => $this->mensajeAfectacion($reserva, $cambio),
                ]
            );
        }
    }

    private function mensajeAfectacion(Reserva $reserva, CambioConfiguracionReserva $cambio): string
    {
        $personalizado = $cambio->valor_nuevo['mensaje_cliente'] ?? null;
        if ($personalizado) {
            return strtr($personalizado, [
                '[fecha]' => $reserva->fecha->format('d/m/Y'),
                '[hora_inicio]' => substr($reserva->hora_inicio_reserva, 0, 5),
                '[hora_fin]' => substr($reserva->hora_fin_reserva, 0, 5),
                '[cancha]' => $reserva->cancha?->nombre ?? 'cancha reservada',
            ]);
        }

        return 'Lamentamos informarte que tu reserva #'.$reserva->id.' del '
            .$reserva->fecha->format('d/m/Y').' de '.substr($reserva->hora_inicio_reserva, 0, 5)
            .' a '.substr($reserva->hora_fin_reserva, 0, 5).' será afectada por un cambio operativo. Motivo: '
            .($cambio->motivo ?: 'ajuste del complejo').'.';
    }
}
