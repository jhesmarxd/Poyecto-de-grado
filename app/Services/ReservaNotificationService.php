<?php

namespace App\Services;

use App\Models\CambioConfiguracionReserva;
use App\Models\Reserva;
use App\Models\User;
use App\Notifications\ReservaNotification;

class ReservaNotificationService
{
    public const TIPO_CONFIRMADA = 'confirmada';

    public const TIPO_RECORDATORIO_2H = 'recordatorio_2h';

    public const TIPO_RECORDATORIO_30M = 'recordatorio_30m';

    public const TIPO_INICIADA = 'iniciada';

    public const TIPO_FINALIZADA = 'finalizada';

    public const TIPO_CANCELADA = 'cancelada';

    public const TIPO_AFECTADA = 'reserva_afectada';

    public const TIPO_REPROGRAMADA = 'reprogramada';

    public const TIPOS = [
        self::TIPO_CONFIRMADA,
        self::TIPO_RECORDATORIO_2H,
        self::TIPO_RECORDATORIO_30M,
        self::TIPO_INICIADA,
        self::TIPO_FINALIZADA,
        self::TIPO_CANCELADA,
        self::TIPO_AFECTADA,
        self::TIPO_REPROGRAMADA,
    ];

    /**
     * Envía una notificación interna una sola vez por reserva y tipo.
     */
    public function enviar(Reserva $reserva, string $tipo): bool
    {
        if (! in_array($tipo, self::TIPOS, true)) {
            return false;
        }

        $reserva->loadMissing(['usuario', 'cancha', 'horario']);
        $usuario = $reserva->usuario;

        if (! $usuario || $usuario->rol !== User::ROL_CLIENTE) {
            return false;
        }

        $yaEnviada = $usuario->notifications()
            ->where('data->reserva_id', $reserva->id)
            ->where('data->tipo', $tipo)
            ->exists();

        if ($yaEnviada) {
            return false;
        }

        $usuario->notify(new ReservaNotification($reserva, $tipo));

        return true;
    }

    public function enviarAfectada(
        Reserva $reserva,
        CambioConfiguracionReserva $cambio,
        string $mensaje,
    ): bool {
        $reserva->loadMissing(['usuario', 'cancha', 'horario']);
        $usuario = $reserva->usuario;
        if (! $usuario || $usuario->rol !== User::ROL_CLIENTE) {
            return false;
        }

        $yaEnviada = $usuario->notifications()
            ->where('data->reserva_id', $reserva->id)
            ->where('data->tipo', self::TIPO_AFECTADA)
            ->where('data->cambio_configuracion_id', $cambio->id)
            ->exists();
        if ($yaEnviada) {
            return true;
        }

        $usuario->notify(new ReservaNotification($reserva, self::TIPO_AFECTADA, $cambio->id, $mensaje));

        return true;
    }
}
