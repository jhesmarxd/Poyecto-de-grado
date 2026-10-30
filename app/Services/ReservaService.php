<?php

namespace App\Services;

use App\Jobs\DeleteGoogleCalendarEvent;
use App\Jobs\SyncReservationToGoogleCalendar;
use App\Models\Balon;
use App\Models\Cancha;
use App\Models\GoogleCalendarConnection;
use App\Models\GoogleCalendarEvent;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReservaService
{
    public function __construct(
        private ?ReservaNotificationService $notificaciones = null,
        private ?PoliticaReservasService $politicaReservas = null,
    ) {}

    /**
     * Valida que la fecha sea válida (formato Y-m-d).
     */
    public function esFechaValida(?string $fecha): bool
    {
        return ($this->politicaReservas ??= app(PoliticaReservasService::class))->fechaValida($fecha);
    }

    /**
     * Obtiene los balones ocupados para una fecha y hora específicas.
     * El bloqueo es por franja horaria, no por cancha.
     *
     * @return array<int>
     */
    public function balonesOcupados(string $fecha, Horario $horario, ?string $horaFin = null): array
    {
        $horaFin ??= $horario->hora_fin;

        return Reserva::query()
            ->activas()
            ->whereDate('fecha', $fecha)
            ->whereNotNull('balon_id')
            ->where('hora_inicio_reserva', '<', $horaFin)
            ->where('hora_fin_reserva', '>', $horario->hora_inicio)
            ->pluck('balon_id')
            ->all();
    }

    /**
     * Verifica si un horario ya está reservado para una cancha y fecha.
     */
    public function horarioOcupado(int $canchaId, string $fecha, int $horarioId): bool
    {
        $horario = Horario::find($horarioId);
        if (! $horario) {
            return true;
        }

        return $this->periodoOcupado($canchaId, $fecha, $horario->hora_inicio, $horario->hora_fin);
    }

    public function periodoOcupado(int $canchaId, string $fecha, string $horaInicio, string $horaFin): bool
    {
        return Reserva::query()
            ->activas()
            ->deCanchaEnFecha($canchaId, $fecha)
            ->where('hora_inicio_reserva', '<', $horaFin)
            ->where('hora_fin_reserva', '>', $horaInicio)
            ->exists();
    }

    /**
     * Verifica si un balón ya está reservado para una fecha y hora específicas.
     * Los balones son compartidos por todo el recinto.
     */
    public function balonOcupado(int $balonId, string $fecha, Horario $horario, ?string $horaFin = null): bool
    {
        $horaFin ??= $horario->hora_fin;

        return Reserva::query()
            ->activas()
            ->whereDate('fecha', $fecha)
            ->where('balon_id', $balonId)
            ->where('hora_inicio_reserva', '<', $horaFin)
            ->where('hora_fin_reserva', '>', $horario->hora_inicio)
            ->exists();
    }

    /**
     * Determina el tipo de balón según el balón seleccionado.
     */
    public function determinarTipoBalon(?Balon $balon): string
    {
        if (! $balon) {
            return Reserva::BALON_PROPIO;
        }

        return $balon->tipo === Balon::TIPO_PREMIUM
            ? Reserva::BALON_PREMIUM
            : Reserva::BALON_PRESTADO;
    }

    /**
     * Calcula el monto total de una reserva.
     */
    public function calcularMonto(float $precioBaseCancha, ?int $balonId, int $duracionMinutos = 60): float
    {
        return Reserva::calcularMonto($precioBaseCancha, $balonId) * ($duracionMinutos / 60);
    }

    /**
     * Crea una reserva validando todas las reglas de negocio.
     *
     * @throws \Exception Si la reserva no puede crearse
     */
    public function crearReserva(User $user, array $data): Reserva
    {
        $reserva = DB::transaction(function () use ($user, $data) {
            // El orden fijo de bloqueos evita que dos solicitudes tomen la misma
            // cancha, horario o balón antes de que la primera transacción termine.
            $cancha = Cancha::disponibles()->lockForUpdate()->findOrFail($data['cancha_id']);
            $horario = $cancha->horarios()
                ->where('disponible', true)
                ->lockForUpdate()
                ->findOrFail($data['horario_id']);

            $fecha = $data['fecha'];

            if (! $this->esFechaValida($fecha)) {
                abort(422, 'La fecha no es válida.');
            }

            $diaSemana = Carbon::parse($fecha)->dayOfWeekIso;
            if ((int) $horario->dia_semana !== $diaSemana) {
                abort(422, 'El horario no corresponde al día de la fecha seleccionada.');
            }

            if (($horario->vigente_desde && $horario->vigente_desde->isAfter($fecha))
                || ($horario->vigente_hasta && $horario->vigente_hasta->isBefore($fecha))) {
                abort(422, 'El horario no está vigente para la fecha seleccionada.');
            }

            $horaFin = Carbon::parse($data['hora_fin'] ?? $horario->hora_fin)->format('H:i:s');
            $inicio = Carbon::parse($fecha.' '.$horario->hora_inicio);
            $fin = Carbon::parse($fecha.' '.$horaFin);
            if ($fin->lessThanOrEqualTo($inicio)) {
                abort(422, 'La hora final debe ser posterior a la hora inicial.');
            }

            // Devuelve un conflicto estable incluso cuando el registro que
            // compite fue creado antes de activar una política operativa nueva.
            if ($this->periodoOcupado($cancha->id, $fecha, $horario->hora_inicio, $horaFin)) {
                abort(409, 'Ese horario ya fue reservado. Por favor elige otro.');
            }

            $politica = $this->politicaReservas ??= app(PoliticaReservasService::class);
            $regla = $politica->validarRangoOperativo($cancha, $fecha, $horario->hora_inicio, $horaFin);
            if (! $regla->disponible()) {
                abort(422, $regla->mensaje);
            }

            $duracionMinutos = (int) $inicio->diffInMinutes($fin);
            $duraciones = array_map('intval', $politica->configuracion()->duraciones_permitidas ?? [60, 90, 120]);
            if (! in_array($duracionMinutos, $duraciones, true)) {
                abort(422, 'La duración permitida debe ser de '.implode(', ', $duraciones).' minutos.');
            }

            $franjas = $cancha->horarios()
                ->where('dia_semana', $diaSemana)
                ->where('disponible', true)
                ->where('hora_inicio', '>=', $horario->hora_inicio)
                ->where('hora_fin', '<=', $horaFin)
                ->where(fn ($query) => $query->whereNull('vigente_desde')->orWhereDate('vigente_desde', '<=', $fecha))
                ->where(fn ($query) => $query->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $fecha))
                ->orderBy('hora_inicio')
                ->lockForUpdate()
                ->get();

            $siguienteInicio = $horario->hora_inicio;
            foreach ($franjas as $franja) {
                if ($franja->hora_inicio !== $siguienteInicio) {
                    abort(422, 'El rango solicitado no está formado por franjas consecutivas.');
                }
                $siguienteInicio = $franja->hora_fin;
            }
            if ($franjas->isEmpty() || $siguienteInicio !== $horaFin) {
                abort(422, 'El rango solicitado no está completamente habilitado.');
            }

            $balonId = $data['balon_id'] ?? null;
            $balon = $balonId
                ? Balon::query()->lockForUpdate()->findOrFail($balonId)
                : null;

            if ($balon && $this->balonOcupado($balon->id, $fecha, $horario, $horaFin)) {
                abort(409, 'Ese balón ya fue reservado para esa fecha y hora. Elige otro balón o trae el tuyo.');
            }

            try {
                return Reserva::create([
                    'user_id' => $user->id,
                    'reprogramada_desde_id' => $data['reprogramada_desde_id'] ?? null,
                    'cancha_id' => $cancha->id,
                    'horario_id' => $horario->id,
                    'fecha' => $fecha,
                    'hora_inicio_reserva' => $horario->hora_inicio,
                    'hora_fin_reserva' => $horaFin,
                    'duracion_minutos' => $duracionMinutos,
                    'monto_total' => $this->calcularMonto((float) $cancha->precio_por_hora, $balonId, $duracionMinutos),
                    'tipo_balon' => $this->determinarTipoBalon($balon),
                    'balon_id' => $balonId,
                    'estado' => Reserva::ESTADO_PENDIENTE,
                    'observaciones' => $data['observaciones'] ?? null,
                ]);
            } catch (QueryException $exception) {
                $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());

                if (in_array($sqlState, ['19', '23000'], true)) {
                    abort(409, 'La franja acaba de ser reservada por otra solicitud. Elige otra.');
                }

                throw $exception;
            }
        });

        $this->programarSincronizacionGoogle($reserva);

        return $reserva;
    }

    /**
     * Cancela una reserva.
     */
    public function cancelarReserva(Reserva $reserva): void
    {
        $reserva->update(['estado' => Reserva::ESTADO_CANCELADA]);
        $this->programarSincronizacionGoogle($reserva);
        $this->notificar($reserva, ReservaNotificationService::TIPO_CANCELADA);
    }

    public function marcarAfectadaPorConfiguracion(Reserva $reserva): void
    {
        $reserva->update(['estado' => Reserva::ESTADO_AFECTADA]);
        $this->programarSincronizacionGoogle($reserva);
    }

    public function cancelarPorComplejo(Reserva $reserva): void
    {
        $reserva->update(['estado' => Reserva::ESTADO_CANCELADA_COMPLEJO]);
        $this->programarSincronizacionGoogle($reserva);
        $this->notificar($reserva, ReservaNotificationService::TIPO_CANCELADA);
    }

    public function marcarReprogramada(Reserva $reserva): void
    {
        $reserva->update(['estado' => Reserva::ESTADO_REPROGRAMADA]);
        $this->programarSincronizacionGoogle($reserva);
    }

    /**
     * Cambia el estado de una reserva.
     *
     * @throws \Exception Si el estado no es válido
     */
    public function cambiarEstado(Reserva $reserva, string $nuevoEstado): void
    {
        // Una reserva ya cancelada o completada no debería volver a estados previos
        if (in_array($reserva->estado, [Reserva::ESTADO_CANCELADA, Reserva::ESTADO_COMPLETADA], true)) {
            abort(422, 'Una reserva '.$reserva->estadoLabel().' no puede cambiar de estado.');
        }

        $reserva->update(['estado' => $nuevoEstado]);
        $this->programarSincronizacionGoogle($reserva);

        $tipoNotificacion = match ($nuevoEstado) {
            Reserva::ESTADO_CONFIRMADA => ReservaNotificationService::TIPO_CONFIRMADA,
            Reserva::ESTADO_CANCELADA => ReservaNotificationService::TIPO_CANCELADA,
            Reserva::ESTADO_COMPLETADA => ReservaNotificationService::TIPO_FINALIZADA,
            default => null,
        };

        if ($tipoNotificacion) {
            $this->notificar($reserva, $tipoNotificacion);
        }
    }

    /**
     * Registra la llegada sin cerrar todavía la reserva.
     */
    public function registrarCheckIn(Reserva $reserva): void
    {
        DB::transaction(function () use ($reserva) {
            $reservaBloqueada = Reserva::query()->lockForUpdate()->findOrFail($reserva->id);

            abort_unless(
                $reservaBloqueada->estado === Reserva::ESTADO_CONFIRMADA
                && $reservaBloqueada->fecha->isToday()
                && $reservaBloqueada->check_in_at === null,
                422,
                'Solo se puede registrar la entrada de una reserva confirmada de hoy.'
            );

            $reservaBloqueada->update(['check_in_at' => now()]);
        });

        $this->notificar($reserva->fresh(), ReservaNotificationService::TIPO_INICIADA);
    }

    /**
     * Registra la salida y recién entonces completa la reserva.
     */
    public function registrarCheckOut(Reserva $reserva): void
    {
        DB::transaction(function () use ($reserva) {
            $reservaBloqueada = Reserva::query()->lockForUpdate()->findOrFail($reserva->id);

            abort_unless(
                in_array($reservaBloqueada->estado, [Reserva::ESTADO_CONFIRMADA, Reserva::ESTADO_COMPLETADA], true)
                && $reservaBloqueada->fecha->isToday()
                && $reservaBloqueada->check_in_at !== null
                && $reservaBloqueada->check_out_at === null,
                422,
                'Solo se puede registrar la salida después de una entrada de hoy.'
            );

            $reservaBloqueada->update([
                'check_out_at' => now(),
                'estado' => Reserva::ESTADO_COMPLETADA,
            ]);
        });

        $this->programarSincronizacionGoogle($reserva);
        $this->notificar($reserva->fresh(), ReservaNotificationService::TIPO_FINALIZADA);
    }

    /**
     * Elimina la reserva y programa la eliminación de su evento externo.
     */
    public function eliminarReserva(Reserva $reserva): void
    {
        $link = $reserva->googleCalendarEvent;
        $eventId = $link?->google_event_id;

        $link?->update([
            'sync_status' => GoogleCalendarEvent::STATUS_PENDING,
            'last_error' => null,
        ]);

        $this->notificar($reserva, ReservaNotificationService::TIPO_CANCELADA);
        $reserva->delete();

        if (config('services.google_calendar.enabled') && filled($eventId)) {
            DeleteGoogleCalendarEvent::dispatch($eventId, $link?->calendar_id, $link?->id)->afterCommit();
        }
    }

    /**
     * Obtiene las reservas de un usuario con sus relaciones.
     */
    public function reservasDelUsuario(User $user)
    {
        return $user->reservas()
            ->with(['cancha', 'horario', 'balon', 'afectacionConfiguracion.cambio', 'googleCalendarEvent'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Obtiene la reserva confirmada vigente o futura más próxima del cliente.
     */
    public function proximaReservaConfirmada(User $user, ?Carbon $ahora = null): ?Reserva
    {
        $zonaHoraria = (string) config('app.timezone', 'UTC');
        $ahora = ($ahora ?? Carbon::now($zonaHoraria))->copy()->setTimezone($zonaHoraria);

        return $user->reservas()
            ->with(['cancha', 'horario'])
            ->where('estado', Reserva::ESTADO_CONFIRMADA)
            ->whereDate('fecha', '>=', $ahora->toDateString())
            ->get()
            ->filter(fn (Reserva $reserva) => $reserva->finalizaEn()->greaterThan($ahora))
            ->sort(function (Reserva $primera, Reserva $segunda): int {
                $porInicio = $primera->iniciaEn()->getTimestamp() <=> $segunda->iniciaEn()->getTimestamp();

                return $porInicio !== 0 ? $porInicio : $primera->id <=> $segunda->id;
            })
            ->first();
    }

    /**
     * Obtiene reservas con filtros para el panel de administración.
     */
    public function reservasConFiltros(?string $estado = null, ?int $canchaId = null)
    {
        return Reserva::query()
            ->with(['usuario', 'cancha', 'horario', 'balon'])
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->when($canchaId, fn ($q) => $q->where('cancha_id', $canchaId))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();
    }

    private function programarSincronizacionGoogle(Reserva $reserva): void
    {
        $link = $reserva->googleCalendarEvent;

        if ($reserva->estado === Reserva::ESTADO_PENDIENTE
            || ($reserva->estaCancelada() && ! $link)
            || ($reserva->estado !== Reserva::ESTADO_CONFIRMADA && ! $link)) {
            return;
        }

        if (! $link && ! config('services.google_calendar.enabled')) {
            return;
        }

        if (! $link) {
            $calendarId = GoogleCalendarConnection::query()
                ->where('enabled', true)
                ->value('calendar_id') ?: config('services.google_calendar.calendar_id', 'primary');
            $link = GoogleCalendarEvent::query()->firstOrCreate(
                ['reserva_id' => $reserva->id],
                ['calendar_id' => $calendarId]
            );
        }

        $link->update([
            'sync_status' => GoogleCalendarEvent::STATUS_PENDING,
            'last_error' => null,
        ]);

        if (config('services.google_calendar.enabled')) {
            SyncReservationToGoogleCalendar::dispatch($reserva->id)->afterCommit();
        }
    }

    private function notificar(Reserva $reserva, string $tipo): void
    {
        ($this->notificaciones ??= app(ReservaNotificationService::class))->enviar($reserva, $tipo);
    }
}
