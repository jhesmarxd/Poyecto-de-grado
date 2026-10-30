<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\GoogleCalendarGateway;
use App\Http\Controllers\Controller;
use App\Models\GoogleCalendarConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class GoogleCalendarController extends Controller
{
    public function __construct(private readonly GoogleCalendarGateway $gateway) {}

    /**
     * Punto técnico para autorizar la única cuenta Google del negocio.
     * No está expuesto en la navegación de la aplicación.
     */
    public function connect(Request $request): RedirectResponse
    {
        if (! $this->gateway->configured()) {
            return redirect()->route('admin.dashboard')->withErrors([
                'google_calendar' => 'Faltan las credenciales OAuth de Google Calendar.',
            ]);
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
            return redirect()->route('admin.dashboard')
                ->withErrors(['google_calendar' => 'La respuesta OAuth no superó la validación de seguridad.']);
        }

        if ($request->filled('error') || ! $request->filled('code')) {
            return redirect()->route('admin.dashboard')
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

            return redirect()->route('admin.dashboard')
                ->withErrors(['google_calendar' => 'No se pudo conectar Google Calendar. Revisa la configuración técnica.']);
        }

        return redirect()->route('admin.dashboard')
            ->with('status', 'Google Calendar quedó conectado.');
    }
}
