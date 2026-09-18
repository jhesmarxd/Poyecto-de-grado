<?php

namespace App\Contracts;

use App\Models\GoogleCalendarConnection;
use App\Models\GoogleCalendarEvent;
use App\Models\Reserva;

interface GoogleCalendarGateway
{
    public function configured(): bool;

    public function authorizationUrl(string $state): string;

    /**
     * @return array{access_token: string, refresh_token: string|null, expires_at: int}
     */
    public function exchangeCode(string $code): array;

    public function accountEmail(string $accessToken): string;

    /**
     * @return array{event_id: string, event_url: string|null, etag: string|null, payload_hash: string}
     */
    public function upsertReservation(
        Reserva $reserva,
        GoogleCalendarConnection $connection,
        GoogleCalendarEvent $link
    ): array;

    /** @return array{calendar_id: string, summary: string|null} */
    public function verifyCalendar(GoogleCalendarConnection $connection): array;

    public function deleteEvent(
        GoogleCalendarConnection $connection,
        string $eventId,
        ?string $calendarId = null
    ): void;
}
