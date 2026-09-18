<?php

namespace App\Jobs;

use App\Contracts\GoogleCalendarGateway;
use App\Models\GoogleCalendarConnection;
use App\Models\GoogleCalendarEvent;
use App\Models\Reserva;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class SyncReservationToGoogleCalendar implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public function __construct(public readonly int $reservaId) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('google-calendar-reserva-'.$this->reservaId))
                ->releaseAfter(30)
                ->expireAfter(180),
        ];
    }

    public function handle(GoogleCalendarGateway $gateway): void
    {
        if (! config('services.google_calendar.enabled') || ! $gateway->configured()) {
            return;
        }

        $connection = GoogleCalendarConnection::query()->where('enabled', true)->first();
        $reserva = Reserva::query()
            ->with(['usuario', 'cancha', 'horario', 'balon', 'googleCalendarEvent'])
            ->find($this->reservaId);

        if (! $connection || ! $reserva) {
            return;
        }

        if ($reserva->estado === Reserva::ESTADO_PENDIENTE) {
            return;
        }

        $link = GoogleCalendarEvent::query()->firstOrCreate([
            'reserva_id' => $reserva->id,
        ], [
            'calendar_id' => $connection->calendar_id,
            'sync_status' => GoogleCalendarEvent::STATUS_PENDING,
        ]);

        $link->update([
            'sync_status' => GoogleCalendarEvent::STATUS_PENDING,
            'last_error' => null,
        ]);

        try {
            $result = $gateway->upsertReservation($reserva, $connection, $link);

            $link->update([
                'google_event_id' => $result['event_id'],
                'google_event_url' => $result['event_url'],
                'etag' => $result['etag'],
                'payload_hash' => $result['payload_hash'],
                'sync_status' => $reserva->estaCancelada()
                    ? GoogleCalendarEvent::STATUS_CANCELLED
                    : GoogleCalendarEvent::STATUS_SYNCED,
                'last_error' => null,
                'synced_at' => now(),
            ]);

            $connection->update(['last_synced_at' => now(), 'last_error' => null]);
        } catch (Throwable $exception) {
            $message = mb_substr($exception->getMessage(), 0, 2000);
            $link->update([
                'sync_status' => GoogleCalendarEvent::STATUS_ERROR,
                'last_error' => $message,
            ]);
            $connection->update(['last_error' => $message]);

            throw $exception;
        }
    }
}
