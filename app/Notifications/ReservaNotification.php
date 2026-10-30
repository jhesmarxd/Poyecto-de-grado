<?php

namespace App\Notifications;

use App\Models\Reserva;
use App\Services\ReservaNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReservaNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Reserva $reserva,
        private readonly string $tipo,
        private readonly ?int $cambioConfiguracionId = null,
        private readonly ?string $mensajePersonalizado = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, int|string>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->reserva->loadMissing(['cancha', 'horario']);
        $inicio = $this->reserva->iniciaEn();

        return [
            'reserva_id' => $this->reserva->id,
            'tipo' => $this->tipo,
            'cambio_configuracion_id' => $this->cambioConfiguracionId,
            'titulo' => $this->titulo(),
            'mensaje' => $this->mensaje(),
            'cancha' => $this->reserva->cancha->nombre,
            'fecha' => $inicio->toDateString(),
            'hora' => $inicio->format('H:i'),
            'url' => route('reservas.mis'),
        ];
    }

    private function titulo(): string
    {
        return match ($this->tipo) {
            ReservaNotificationService::TIPO_CONFIRMADA => 'Reserva confirmada',
            ReservaNotificationService::TIPO_RECORDATORIO_2H => 'Tu reserva se acerca',
            ReservaNotificationService::TIPO_RECORDATORIO_30M => 'Faltan 30 minutos',
            ReservaNotificationService::TIPO_INICIADA => 'Reserva iniciada',
            ReservaNotificationService::TIPO_FINALIZADA => 'Reserva finalizada',
            ReservaNotificationService::TIPO_CANCELADA => 'Reserva cancelada',
            ReservaNotificationService::TIPO_AFECTADA => 'Tu reserva fue afectada',
            ReservaNotificationService::TIPO_REPROGRAMADA => 'Reserva reprogramada',
            default => 'Actualización de reserva',
        };
    }

    private function mensaje(): string
    {
        if ($this->mensajePersonalizado) {
            return $this->mensajePersonalizado;
        }

        $cancha = $this->reserva->cancha->nombre;
        $hora = $this->reserva->iniciaEn()->format('H:i');

        return match ($this->tipo) {
            ReservaNotificationService::TIPO_CONFIRMADA => "Tu reserva #{$this->reserva->id} en {$cancha} fue confirmada.",
            ReservaNotificationService::TIPO_RECORDATORIO_2H => "Tu reserva en {$cancha} comienza en menos de 2 horas, a las {$hora}.",
            ReservaNotificationService::TIPO_RECORDATORIO_30M => "Tu reserva en {$cancha} comienza en menos de 30 minutos.",
            ReservaNotificationService::TIPO_INICIADA => "El horario de tu reserva en {$cancha} acaba de comenzar.",
            ReservaNotificationService::TIPO_FINALIZADA => "El horario de tu reserva en {$cancha} ha finalizado.",
            ReservaNotificationService::TIPO_CANCELADA => "Tu reserva #{$this->reserva->id} en {$cancha} fue cancelada.",
            ReservaNotificationService::TIPO_AFECTADA => "Tu reserva #{$this->reserva->id} en {$cancha} fue afectada por un cambio operativo del complejo.",
            ReservaNotificationService::TIPO_REPROGRAMADA => "Tu reserva #{$this->reserva->id} fue reprogramada.",
            default => "Hay una actualización en tu reserva #{$this->reserva->id}.",
        };
    }
}
