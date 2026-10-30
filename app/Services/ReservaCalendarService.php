<?php

namespace App\Services;

use App\Models\GoogleCalendarEvent;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReservaCalendarService
{
    public function paraAdministrador(
        Carbon $inicio,
        Carbon $finExclusivo,
        ?int $canchaId = null,
        ?string $estado = null
    ): array {
        return $this->consultar($inicio, $finExclusivo, $canchaId, $estado)
            ->map(fn (Reserva $reserva) => $this->eventoAdministrador($reserva))
            ->all();
    }

    public function paraRecepcionista(
        Carbon $inicio,
        Carbon $finExclusivo,
        ?int $canchaId = null,
        ?string $estado = null
    ): array {
        return $this->consultar($inicio, $finExclusivo, $canchaId, $estado)
            ->map(fn (Reserva $reserva) => $this->eventoRecepcionista($reserva))
            ->all();
    }

    public function paraCliente(
        User $cliente,
        Carbon $inicio,
        Carbon $finExclusivo,
        ?string $estado = null
    ): array {
        return $this->consultar($inicio, $finExclusivo, null, $estado, true, $cliente->id)
            ->map(fn (Reserva $reserva) => $this->eventoCliente($reserva))
            ->all();
    }

    private function consultar(
        Carbon $inicio,
        Carbon $finExclusivo,
        ?int $canchaId,
        ?string $estado,
        bool $conGoogle = false,
        ?int $clienteId = null
    ): Collection {
        $relaciones = ['usuario', 'cancha', 'horario', 'balon'];
        if ($conGoogle) {
            $relaciones[] = 'googleCalendarEvent';
        }

        return Reserva::query()
            ->with($relaciones)
            ->whereDate('fecha', '>=', $inicio->toDateString())
            ->whereDate('fecha', '<', $finExclusivo->toDateString())
            ->when($canchaId, fn (Builder $query) => $query->where('cancha_id', $canchaId))
            ->when($estado, fn (Builder $query) => $query->where('estado', $estado))
            ->when($clienteId, fn (Builder $query) => $query->where('user_id', $clienteId))
            ->orderBy('fecha')
            ->orderBy(
                Horario::query()
                    ->select('hora_inicio')
                    ->whereColumn('horarios.id', 'reservas.horario_id')
                    ->limit(1)
            )
            ->get();
    }

    private function eventoAdministrador(Reserva $reserva): array
    {
        $base = $this->eventoBase(
            $reserva,
            $reserva->iniciaEn()->format('H:i').' · '.$reserva->usuario->name.' · '.$reserva->cancha->nombre
        );
        $base['extendedProps'] = [
            'estado' => $reserva->estadoLabel(),
            'cliente' => $reserva->usuario->name,
            'correo' => $reserva->usuario->email,
            'telefono' => $reserva->usuario->telefono,
            'cancha' => $reserva->cancha->nombre,
            'fecha' => $reserva->fecha->format('d/m/Y'),
            'hora_inicio' => $reserva->iniciaEn()->format('H:i'),
            'hora_fin' => $reserva->finalizaEn()->format('H:i'),
            'balon' => $reserva->balonLabelCorta(),
            'monto' => 'Bs. '.number_format((float) $reserva->monto_total, 2),
            'observaciones' => $reserva->observaciones,
            'check_in' => $reserva->check_in_at?->format('H:i'),
            'check_out' => $reserva->check_out_at?->format('H:i'),
        ];

        return $base;
    }

    private function eventoRecepcionista(Reserva $reserva): array
    {
        $base = $this->eventoBase(
            $reserva,
            $reserva->iniciaEn()->format('H:i').' · '.$reserva->usuario->name.' · '.$reserva->cancha->nombre
        );
        $base['extendedProps'] = [
            'estado' => $reserva->estadoOperacionLabel(),
            'cliente' => $reserva->usuario->name,
            'telefono' => $reserva->usuario->telefono,
            'cancha' => $reserva->cancha->nombre,
            'fecha' => $reserva->fecha->format('d/m/Y'),
            'hora_inicio' => $reserva->iniciaEn()->format('H:i'),
            'hora_fin' => $reserva->finalizaEn()->format('H:i'),
            'balon' => $reserva->balonLabelCorta(),
            'check_in' => $reserva->check_in_at?->format('H:i'),
            'check_out' => $reserva->check_out_at?->format('H:i'),
        ];

        return $base;
    }

    private function eventoCliente(Reserva $reserva): array
    {
        $base = $this->eventoBase($reserva, $reserva->cancha->nombre);
        $google = $reserva->googleCalendarEvent;
        $base['extendedProps'] = [
            'estado' => $reserva->estadoLabel(),
            'cancha' => $reserva->cancha->nombre,
            'fecha' => $reserva->fecha->format('d/m/Y'),
            'hora_inicio' => $reserva->iniciaEn()->format('H:i'),
            'hora_fin' => $reserva->finalizaEn()->format('H:i'),
            'google_estado' => $this->estadoGoogleCliente($google),
            'google_url' => $google?->urlSegura(),
        ];

        return $base;
    }

    private function eventoBase(Reserva $reserva, string $titulo): array
    {
        $timezone = (string) config('app.timezone', 'America/La_Paz');
        $fecha = $reserva->fecha->toDateString();
        $inicio = $reserva->iniciaEn();
        $fin = $reserva->finalizaEn();
        $color = Reserva::COLORES[$reserva->estado] ?? Reserva::COLORES[Reserva::ESTADO_COMPLETADA];

        return [
            'id' => (string) $reserva->id,
            'title' => $titulo,
            'start' => $inicio->toIso8601String(),
            'end' => $fin->toIso8601String(),
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => $reserva->estado === Reserva::ESTADO_PENDIENTE ? '#3b2f00' : '#ffffff',
            'classNames' => ['reserva-evento', 'reserva-estado-'.$reserva->estado],
        ];
    }

    private function hora(string $hora): string
    {
        return substr($hora, 0, 5);
    }

    private function estadoGoogleCliente(?GoogleCalendarEvent $evento): string
    {
        return match ($evento?->sync_status) {
            GoogleCalendarEvent::STATUS_PENDING => 'Sincronizando con Google Calendar',
            GoogleCalendarEvent::STATUS_SYNCED => 'Agendada en Google Calendar',
            GoogleCalendarEvent::STATUS_ERROR => 'No se pudo agendar en Google Calendar',
            GoogleCalendarEvent::STATUS_CANCELLED, GoogleCalendarEvent::STATUS_DELETED => 'Cita de Google Calendar cancelada',
            default => 'Aún no agendada en Google Calendar',
        };
    }

    private function estadoGoogle(?GoogleCalendarEvent $evento): string
    {
        return match ($evento?->sync_status) {
            GoogleCalendarEvent::STATUS_PENDING => 'Pendiente de sincronización',
            GoogleCalendarEvent::STATUS_SYNCED => 'Sincronizada',
            GoogleCalendarEvent::STATUS_ERROR => 'Con error',
            GoogleCalendarEvent::STATUS_CANCELLED => 'Cancelada sincronizada',
            GoogleCalendarEvent::STATUS_DELETED => 'Eliminada',
            default => 'Sin evento externo',
        };
    }

    private function urlGoogleSegura(?string $url): ?string
    {
        if (! $url || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return $scheme === 'https' && in_array($host, ['calendar.google.com', 'www.google.com'], true)
            ? $url
            : null;
    }
}
