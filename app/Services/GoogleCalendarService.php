<?php

namespace App\Services;

use App\Contracts\GoogleCalendarGateway;
use App\Models\GoogleCalendarConnection;
use App\Models\GoogleCalendarEvent;
use App\Models\Reserva;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleCalendarService implements GoogleCalendarGateway
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    private const API_URL = 'https://www.googleapis.com/calendar/v3';

    private const SCOPES = [
        'openid',
        'email',
        'https://www.googleapis.com/auth/calendar.events',
        'https://www.googleapis.com/auth/calendar.calendarlist.readonly',
    ];

    public function configured(): bool
    {
        return filled(config('services.google_calendar.client_id'))
            && filled(config('services.google_calendar.client_secret'))
            && filled(config('services.google_calendar.redirect'));
    }

    public function authorizationUrl(string $state): string
    {
        $this->assertConfigured();

        return self::AUTH_URL.'?'.http_build_query([
            'client_id' => config('services.google_calendar.client_id'),
            'redirect_uri' => config('services.google_calendar.redirect'),
            'response_type' => 'code',
            'scope' => implode(' ', self::SCOPES),
            'access_type' => 'offline',
            'prompt' => 'consent select_account',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function exchangeCode(string $code): array
    {
        $this->assertConfigured();

        $response = Http::asForm()->acceptJson()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => config('services.google_calendar.client_id'),
            'client_secret' => config('services.google_calendar.client_secret'),
            'redirect_uri' => config('services.google_calendar.redirect'),
            'grant_type' => 'authorization_code',
        ]);

        $this->throwForGoogleError($response, 'Google rechazó el código de autorización.');
        $token = $response->json();

        if (blank($token['access_token'] ?? null)) {
            throw new RuntimeException('Google no entregó un token de acceso.');
        }

        return [
            'access_token' => (string) $token['access_token'],
            'refresh_token' => isset($token['refresh_token']) ? (string) $token['refresh_token'] : null,
            'expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 3600))->timestamp,
        ];
    }

    public function accountEmail(string $accessToken): string
    {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get(self::USERINFO_URL);

        $this->throwForGoogleError($response, 'No se pudo identificar la cuenta de Google autorizada.');

        $email = $response->json('email');
        $verified = filter_var($response->json('email_verified'), FILTER_VALIDATE_BOOL);

        if (! is_string($email) || blank($email) || ! $verified) {
            throw new RuntimeException('Google no proporcionó un correo verificado para la cuenta autorizada.');
        }

        return $email;
    }

    public function upsertReservation(
        Reserva $reserva,
        GoogleCalendarConnection $connection,
        GoogleCalendarEvent $link
    ): array {
        $reserva->loadMissing(['usuario', 'cancha', 'horario', 'balon']);
        $accessToken = $this->accessToken($connection);
        $calendarId = rawurlencode($link->calendar_id ?: $connection->calendar_id ?: 'primary');
        $eventId = $link->google_event_id ?: $this->eventIdFor($reserva);
        $payload = $this->eventFor($reserva, $eventId);
        $baseUrl = self::API_URL.'/calendars/'.$calendarId.'/events';

        $response = $link->synced_at
            ? Http::withToken($accessToken)->acceptJson()->put($baseUrl.'/'.rawurlencode($eventId), $payload)
            : Http::withToken($accessToken)->acceptJson()->post($baseUrl, $payload);

        if ($response->status() === 404 && $link->synced_at) {
            $response = Http::withToken($accessToken)->acceptJson()->post($baseUrl, $payload);
        }

        if ($response->status() === 409) {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->put($baseUrl.'/'.rawurlencode($eventId), $payload);
        }

        $this->throwForGoogleError($response, 'No se pudo guardar el evento en Google Calendar.');
        $saved = $response->json();

        return [
            'event_id' => (string) ($saved['id'] ?? $eventId),
            'event_url' => isset($saved['htmlLink']) ? (string) $saved['htmlLink'] : null,
            'etag' => isset($saved['etag']) ? (string) $saved['etag'] : null,
            'payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
        ];
    }

    public function verifyCalendar(GoogleCalendarConnection $connection): array
    {
        $calendarId = $connection->calendar_id ?: 'primary';
        $response = Http::withToken($this->accessToken($connection))
            ->acceptJson()
            ->get(self::API_URL.'/users/me/calendarList/'.rawurlencode($calendarId));

        $this->throwForGoogleError($response, 'No se pudo comprobar el acceso al calendario.');

        return [
            'calendar_id' => (string) ($response->json('id') ?: $calendarId),
            'summary' => $response->json('summary'),
        ];
    }

    public function deleteEvent(
        GoogleCalendarConnection $connection,
        string $eventId,
        ?string $calendarId = null
    ): void {
        $calendarId = rawurlencode($calendarId ?: $connection->calendar_id ?: 'primary');
        $response = Http::withToken($this->accessToken($connection))
            ->acceptJson()
            ->delete(self::API_URL.'/calendars/'.$calendarId.'/events/'.rawurlencode($eventId));

        if (! in_array($response->status(), [404, 410], true)) {
            $this->throwForGoogleError($response, 'No se pudo eliminar el evento de Google Calendar.');
        }
    }

    private function accessToken(GoogleCalendarConnection $connection): string
    {
        $this->assertConfigured();

        if ($connection->token_expires_at?->gt(now()->addMinute())) {
            return $connection->access_token;
        }

        if (blank($connection->refresh_token)) {
            return $connection->access_token;
        }

        $response = Http::asForm()->acceptJson()->post(self::TOKEN_URL, [
            'client_id' => config('services.google_calendar.client_id'),
            'client_secret' => config('services.google_calendar.client_secret'),
            'refresh_token' => $connection->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        $this->throwForGoogleError($response, 'No se pudo renovar la autorización de Google Calendar.');
        $token = $response->json();

        if (blank($token['access_token'] ?? null)) {
            throw new RuntimeException('Google no entregó un token de acceso renovado.');
        }

        $connection->update([
            'access_token' => (string) $token['access_token'],
            'refresh_token' => (string) ($token['refresh_token'] ?? $connection->refresh_token),
            'token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 3600)),
            'last_error' => null,
        ]);

        return (string) $token['access_token'];
    }

    /** @return array<string, mixed> */
    private function eventFor(Reserva $reserva, string $eventId): array
    {
        $timezone = (string) config('app.timezone', 'America/La_Paz');
        $start = $reserva->iniciaEn();
        $end = $reserva->finalizaEn();

        $description = array_filter([
            'Reserva #'.$reserva->id,
            'Cliente: '.$reserva->usuario->name,
            'Cancha: '.$reserva->cancha->nombre,
            'Horario: '.$start->format('H:i').' - '.$end->format('H:i'),
            'Duración: '.$this->durationLabel((int) $start->diffInMinutes($end)),
            'Estado: '.$reserva->estadoLabel(),
            'Balón: '.$reserva->balonLabelCorta(),
            $reserva->observaciones ? 'Observaciones: '.$reserva->observaciones : null,
        ]);

        $prefix = match ($reserva->estado) {
            Reserva::ESTADO_CANCELADA => '[CANCELADA] ',
            Reserva::ESTADO_COMPLETADA => '[COMPLETADA] ',
            default => '',
        };

        $event = [
            'id' => $eventId,
            'summary' => $prefix.'Reserva Wally - '.$reserva->cancha->nombre,
            'description' => implode("\n", $description),
            'location' => $reserva->cancha->ubicacion,
            'visibility' => 'private',
            'start' => ['dateTime' => $start->toRfc3339String(), 'timeZone' => $timezone],
            'end' => ['dateTime' => $end->toRfc3339String(), 'timeZone' => $timezone],
            'reminders' => [
                'useDefault' => false,
                'overrides' => $this->reminders(),
            ],
            'extendedProperties' => [
                'private' => [
                    'wally_reserva_id' => (string) $reserva->id,
                    'wally_estado' => $reserva->estado,
                ],
            ],
        ];

        if ($reserva->estado === Reserva::ESTADO_CANCELADA) {
            $event['colorId'] = '11';
        }

        return $event;
    }

    /** @return list<array{method: string, minutes: int}> */
    private function reminders(): array
    {
        return collect(config('services.google_calendar.reminder_minutes', [1440, 120, 30]))
            ->filter(fn ($minutes) => is_numeric(trim((string) $minutes)))
            ->map(fn ($minutes) => (int) $minutes)
            ->filter(fn (int $minutes) => $minutes >= 0 && $minutes <= 40320)
            ->unique()
            ->take(5)
            ->map(fn (int $minutes) => ['method' => 'popup', 'minutes' => $minutes])
            ->values()
            ->all();
    }

    private function durationLabel(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;
        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours.' '.($hours === 1 ? 'hora' : 'horas');
        }

        if ($remainingMinutes > 0 || $parts === []) {
            $parts[] = $remainingMinutes.' '.($remainingMinutes === 1 ? 'minuto' : 'minutos');
        }

        return implode(' ', $parts);
    }

    private function eventIdFor(Reserva $reserva): string
    {
        return hash('sha256', config('app.key').'|wally-reserva|'.$reserva->id);
    }

    private function assertConfigured(): void
    {
        if (! $this->configured()) {
            throw new RuntimeException('Las credenciales OAuth de Google Calendar no están configuradas.');
        }
    }

    private function throwForGoogleError(Response $response, string $fallback): void
    {
        if ($response->successful()) {
            return;
        }

        throw new RuntimeException(
            (string) data_get($response->json(), 'error.message', $fallback),
            $response->status()
        );
    }
}
