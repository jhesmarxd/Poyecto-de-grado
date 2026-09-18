<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\GoogleCalendarGateway;
use App\Http\Controllers\Controller;
use App\Jobs\DeleteGoogleCalendarEvent;
use App\Jobs\SyncReservationToGoogleCalendar;
use App\Models\GoogleCalendarConnection;
use App\Models\GoogleCalendarEvent;
use App\Models\Reserva;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class GoogleCalendarController extends Controller
{
    public function __construct(private readonly GoogleCalendarGateway $gateway) {}

    public function index(): View
    {
        return view('admin.integraciones.google-calendar', [
            'connection' => GoogleCalendarConnection::query()->first(),
            'configured' => $this->gateway->configured(),
            'globallyEnabled' => (bool) config('services.google_calendar.enabled'),
            'syncCounts' => GoogleCalendarEvent::query()
                ->selectRaw('sync_status, count(*) as total')
                ->groupBy('sync_status')
                ->pluck('total', 'sync_status'),
        ]);
    }

    public function connect(Request $request): RedirectResponse
    {
        if (! $this->gateway->configured()) {
            return back()->withErrors(['google_calendar' => 'Faltan las credenciales OAuth de Google Calendar.']);
        }

        $state = Str::random(64);
        $request->session()->put('google_calendar_oauth_state', $state);

        return redirect()->away($this->gateway->authorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('google_calendar_oauth_state');
        $receivedState = (string) $request->query('state');

        if (blank($expectedState) || blank($receivedState) || ! hash_equals($expectedState, $receivedState)) {
            return redirect()->route('admin.integraciones.google-calendar.index')
                ->withErrors(['google_calendar' => 'La respuesta OAuth no superó la validación de seguridad.']);
        }

        if ($request->filled('error') || ! $request->filled('code')) {
            return redirect()->route('admin.integraciones.google-calendar.index')
                ->withErrors(['google_calendar' => 'Google no autorizó el acceso al calendario.']);
        }

        try {
            $token = $this->gateway->exchangeCode((string) $request->query('code'));
            $accountEmail = $this->gateway->accountEmail($token['access_token']);
            $connection = GoogleCalendarConnection::query()->first();
            $refreshToken = $token['refresh_token'] ?: $connection?->refresh_token;

            if (blank($refreshToken)) {
                throw new \RuntimeException('Google no entregó un token de renovación. Revoca el acceso y vuelve a conectar.');
            }

            $values = [
                'connected_by' => $request->user()->id,
                'account_email' => $accountEmail,
                'calendar_id' => $connection?->calendar_id ?: config('services.google_calendar.calendar_id', 'primary'),
                'access_token' => $token['access_token'],
                'refresh_token' => $refreshToken,
                'token_expires_at' => $token['expires_at'],
                'enabled' => true,
                'connected_at' => now(),
                'last_error' => null,
            ];

            $connection ? $connection->update($values) : GoogleCalendarConnection::create($values);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('admin.integraciones.google-calendar.index')
                ->withErrors(['google_calendar' => $exception->getMessage()]);
        }

        return redirect()->route('admin.integraciones.google-calendar.index')
            ->with('status', 'Google Calendar quedó conectado. La sincronización no se ejecutó automáticamente.');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'calendar_id' => ['required', 'string', 'max:255'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        $connection = GoogleCalendarConnection::query()->firstOrFail();
        $calendarId = trim($data['calendar_id']);

        if ($calendarId !== $connection->calendar_id
            && GoogleCalendarEvent::query()
                ->whereNotNull('google_event_id')
                ->where('sync_status', '!=', GoogleCalendarEvent::STATUS_DELETED)
                ->exists()) {
            return back()->withErrors([
                'google_calendar' => 'No se puede cambiar el calendario mientras existan eventos externos activos. Mígralos o elimínalos antes de continuar.',
            ]);
        }

        DB::transaction(function () use ($connection, $calendarId, $request): void {
            $connection->update([
                'calendar_id' => $calendarId,
                'enabled' => $request->boolean('enabled'),
            ]);

            GoogleCalendarEvent::query()
                ->whereNull('google_event_id')
                ->update(['calendar_id' => $calendarId]);
        });

        return back()->with('status', 'Configuración de Calendar actualizada.');
    }

    public function disconnect(): RedirectResponse
    {
        GoogleCalendarConnection::query()->delete();
        GoogleCalendarEvent::query()->update([
            'sync_status' => GoogleCalendarEvent::STATUS_PENDING,
            'synced_at' => null,
            'last_error' => null,
        ]);

        return back()->with('status', 'Conexión local con Google Calendar eliminada.');
    }

    public function syncUpcoming(): RedirectResponse
    {
        if (! config('services.google_calendar.enabled')) {
            return back()->withErrors([
                'google_calendar' => 'La sincronización está protegida. Activa GOOGLE_CALENDAR_ENABLED antes de ejecutarla.',
            ]);
        }

        $connection = GoogleCalendarConnection::query()->where('enabled', true)->first();
        if (! $connection) {
            return back()->withErrors(['google_calendar' => 'Conecta y habilita primero Google Calendar.']);
        }

        $ids = Reserva::query()
            ->where('estado', Reserva::ESTADO_CONFIRMADA)
            ->whereDate('fecha', '>=', today())
            ->pluck('id')
            ->merge(
                Reserva::query()
                    ->whereIn('estado', [
                        Reserva::ESTADO_CONFIRMADA,
                        Reserva::ESTADO_CANCELADA,
                        Reserva::ESTADO_COMPLETADA,
                    ])
                    ->whereHas('googleCalendarEvent', fn ($query) => $query->whereIn('sync_status', [
                        GoogleCalendarEvent::STATUS_PENDING,
                        GoogleCalendarEvent::STATUS_ERROR,
                    ]))
                    ->pluck('id')
            )
            ->unique()
            ->values();
        foreach ($ids as $id) {
            SyncReservationToGoogleCalendar::dispatch((int) $id);
        }

        $huerfanos = GoogleCalendarEvent::query()
            ->whereNull('reserva_id')
            ->whereNotNull('google_event_id')
            ->whereIn('sync_status', [GoogleCalendarEvent::STATUS_PENDING, GoogleCalendarEvent::STATUS_ERROR])
            ->get();
        foreach ($huerfanos as $link) {
            DeleteGoogleCalendarEvent::dispatch($link->google_event_id, $link->calendar_id, $link->id);
        }

        return back()->with('status', ($ids->count() + $huerfanos->count()).' operaciones enviadas a la cola de sincronización.');
    }

    public function retry(Reserva $reserva): RedirectResponse
    {
        if (! config('services.google_calendar.enabled')) {
            return back()->withErrors(['google_calendar' => 'Google Calendar está desactivado en la configuración.']);
        }

        if ($reserva->estado === Reserva::ESTADO_PENDIENTE) {
            return back()->withErrors(['google_calendar' => 'Las reservas pendientes no generan eventos.']);
        }

        SyncReservationToGoogleCalendar::dispatch($reserva->id);

        return back()->with('status', "Reserva #{$reserva->id} enviada nuevamente a sincronización.");
    }

    public function verify(): RedirectResponse
    {
        if (! config('services.google_calendar.enabled')) {
            return back()->withErrors(['google_calendar' => 'Activa Google Calendar antes de comprobar la conexión.']);
        }

        $connection = GoogleCalendarConnection::query()->where('enabled', true)->first();
        if (! $connection) {
            return back()->withErrors(['google_calendar' => 'Conecta y habilita primero Google Calendar.']);
        }

        try {
            $calendar = $this->gateway->verifyCalendar($connection);

            return back()->with('status', 'Conexión verificada con el calendario: '.($calendar['summary'] ?: $calendar['calendar_id']).'.');
        } catch (Throwable $exception) {
            report($exception);
            $connection->update(['last_error' => mb_substr($exception->getMessage(), 0, 2000)]);

            return back()->withErrors(['google_calendar' => 'No se pudo verificar el calendario. Revisa OAuth, permisos y el ID configurado.']);
        }
    }
}
