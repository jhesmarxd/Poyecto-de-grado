<?php

namespace App\Jobs;

use App\Contracts\GoogleCalendarGateway;
use App\Models\GoogleCalendarConnection;
use App\Models\GoogleCalendarEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class DeleteGoogleCalendarEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public function __construct(
        public readonly string $eventId,
        public readonly ?string $calendarId = null,
        public readonly ?int $linkId = null,
    ) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(GoogleCalendarGateway $gateway): void
    {
        if (! config('services.google_calendar.enabled') || ! $gateway->configured()) {
            return;
        }

        $connection = GoogleCalendarConnection::query()->where('enabled', true)->first();
        if ($connection) {
            try {
                $gateway->deleteEvent($connection, $this->eventId, $this->calendarId);
                GoogleCalendarEvent::query()->whereKey($this->linkId)->update([
                    'sync_status' => GoogleCalendarEvent::STATUS_DELETED,
                    'last_error' => null,
                    'synced_at' => now(),
                ]);
            } catch (Throwable $exception) {
                GoogleCalendarEvent::query()->whereKey($this->linkId)->update([
                    'sync_status' => GoogleCalendarEvent::STATUS_ERROR,
                    'last_error' => mb_substr($exception->getMessage(), 0, 2000),
                ]);

                throw $exception;
            }
        }
    }
}
