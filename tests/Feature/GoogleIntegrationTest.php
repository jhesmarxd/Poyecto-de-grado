<?php

namespace Tests\Feature;

use App\Contracts\GoogleCalendarGateway;
use App\Jobs\SyncReservationToGoogleCalendar;
use App\Models\GoogleCalendarConnection;
use App\Models\GoogleCalendarEvent;
use App\Models\Cancha;
use App\Models\Reserva;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\ReservaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

class GoogleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_login_creates_only_a_client_with_verified_email(): void
    {
        config()->set('services.google', [
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'redirect' => 'http://localhost/auth/google/callback',
        ]);

        $googleUser = GoogleUser::fake([
            'id' => 'google-user-123',
            'name' => 'Cliente Google',
            'email' => 'cliente.google@example.com',
            'email_verified' => true,
        ]);
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->withSession(['google_oauth_intent' => 'login'])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('home'));

        $user = User::query()->where('email', 'cliente.google@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(User::ROL_CLIENTE, $user->rol);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => SocialAccount::PROVIDER_GOOGLE,
            'provider_user_id' => 'google-user-123',
        ]);
    }

    public function test_primer_ingreso_con_google_autentica_al_cliente_y_retoma_su_consulta(): void
    {
        $cancha = Cancha::factory()->create();
        $fecha = now()->addDay()->toDateString();
        $destino = route('disponibilidad.index', ['cancha_id' => $cancha->id, 'fecha' => $fecha]);

        $this->get(route('disponibilidad.index', [
            'consultar' => 1, 'cancha_id' => $cancha->id, 'fecha' => $fecha,
        ]))->assertRedirect(route('login'));

        $googleUser = GoogleUser::fake([
            'id' => 'google-primer-ingreso',
            'name' => 'Cliente Nuevo',
            'email' => 'primer.ingreso@example.com',
            'email_verified' => true,
        ]);
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->withSession(['google_oauth_intent' => 'login'])
            ->get(route('auth.google.callback'))
            ->assertRedirect($destino);

        $user = User::query()->where('email', 'primer.ingreso@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(User::ROL_CLIENTE, $user->rol);
        $this->assertNull($user->password);
    }

    public function test_google_login_rejects_an_unverified_email(): void
    {
        $googleUser = GoogleUser::fake([
            'id' => 'google-user-unverified',
            'email' => 'sin-verificar@example.com',
            'email_verified' => false,
        ]);
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->withSession(['google_oauth_intent' => 'login'])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'sin-verificar@example.com']);
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_google_login_rejects_an_inactive_linked_user(): void
    {
        $user = User::factory()->create(['estado' => false]);
        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => SocialAccount::PROVIDER_GOOGLE,
            'provider_user_id' => 'google-user-inactive',
            'provider_email' => $user->email,
        ]);
        $googleUser = GoogleUser::fake([
            'id' => 'google-user-inactive',
            'email' => $user->email,
            'email_verified' => true,
        ]);
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->withSession(['google_oauth_intent' => 'login'])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    public function test_google_login_does_not_silently_link_an_existing_email(): void
    {
        User::factory()->create(['email' => 'existente@example.com']);
        $googleUser = GoogleUser::fake([
            'id' => 'google-user-other',
            'email' => 'existente@example.com',
            'email_verified' => true,
        ]);
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->withSession(['google_oauth_intent' => 'login'])
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_calendar_does_not_have_a_visual_admin_module(): void
    {
        Http::preventStrayRequests();
        $this->assertFalse(Route::has('admin.integraciones.google-calendar.index'));
        $this->assertTrue(Route::has('admin.integraciones.google-calendar.connect'));
        Http::assertNothingSent();
    }

    public function test_calendar_callback_stores_fake_tokens_after_validating_state(): void
    {
        Http::preventStrayRequests();
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
        $gateway = Mockery::mock(GoogleCalendarGateway::class);
        $gateway->shouldReceive('exchangeCode')->once()->with('fake-code')->andReturn([
            'access_token' => 'fake-access-token',
            'refresh_token' => 'fake-refresh-token',
            'expires_at' => now()->addHour()->timestamp,
        ]);
        $gateway->shouldReceive('accountEmail')->once()->with('fake-access-token')
            ->andReturn('cuenta-google@example.com');
        $this->app->instance(GoogleCalendarGateway::class, $gateway);

        $this->actingAs($admin)
            ->withSession(['google_calendar_oauth_state' => 'valid-state'])
            ->get(route('admin.integraciones.google-calendar.callback', [
                'state' => 'valid-state',
                'code' => 'fake-code',
            ]))
            ->assertRedirect(route('admin.dashboard'));

        $connection = GoogleCalendarConnection::query()->firstOrFail();
        $this->assertSame('fake-access-token', $connection->access_token);
        $this->assertSame('fake-refresh-token', $connection->refresh_token);
        $this->assertSame($admin->id, $connection->connected_by);
        $this->assertSame('cuenta-google@example.com', $connection->account_email);
        Http::assertNothingSent();
    }

    public function test_calendar_obtiene_la_cuenta_google_real_desde_userinfo(): void
    {
        Http::fake([
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'email' => 'calendario-real@example.com',
                'email_verified' => true,
            ]),
        ]);

        $email = app(GoogleCalendarService::class)->accountEmail('access-token');

        $this->assertSame('calendario-real@example.com', $email);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer access-token'));
    }

    public function test_calendar_job_uses_only_fake_http_and_saves_external_id(): void
    {
        config()->set('services.google_calendar', [
            'client_id' => 'calendar-client-id',
            'client_secret' => 'calendar-client-secret',
            'redirect' => 'http://localhost/admin/integraciones/google-calendar/callback',
            'enabled' => true,
            'calendar_id' => 'primary',
        ]);

        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
        $connection = GoogleCalendarConnection::create([
            'connected_by' => $admin->id,
            'account_email' => $admin->email,
            'calendar_id' => 'primary',
            'access_token' => 'fake-access-token',
            'refresh_token' => 'fake-refresh-token',
            'token_expires_at' => now()->addHour(),
            'enabled' => true,
            'connected_at' => now(),
        ]);
        $reserva = Reserva::factory()->confirmada()->create();

        Http::preventStrayRequests();
        Http::fake([
            'https://www.googleapis.com/calendar/v3/*' => Http::response([
                'id' => 'fake-google-event-id',
                'htmlLink' => 'https://calendar.google.com/calendar/event?eid=fake',
                'etag' => 'fake-etag',
            ], 200),
        ]);

        (new SyncReservationToGoogleCalendar($reserva->id))
            ->handle(app(GoogleCalendarGateway::class));

        $this->assertDatabaseHas('google_calendar_events', [
            'reserva_id' => $reserva->id,
            'google_event_id' => 'fake-google-event-id',
            'sync_status' => GoogleCalendarEvent::STATUS_SYNCED,
        ]);
        Http::assertSent(function ($request) use ($reserva) {
            $data = $request->data();

            return $request->method() === 'POST'
                && $data['summary'] === 'Reserva Wally - '.$reserva->cancha->nombre
                && str_contains($data['description'], 'Cliente: '.$reserva->usuario->name)
                && str_contains($data['description'], 'Cancha: '.$reserva->cancha->nombre)
                && str_contains($data['description'], 'Horario:')
                && str_contains($data['description'], 'Duración:')
                && str_contains($data['description'], 'Estado: Confirmada')
                && $data['attendees'] === [['email' => $reserva->usuario->email]]
                && $data['start']['timeZone'] === 'America/La_Paz'
                && $data['end']['timeZone'] === 'America/La_Paz'
                && $data['reminders'] === [
                    'useDefault' => false,
                    'overrides' => [
                        ['method' => 'popup', 'minutes' => 1440],
                        ['method' => 'popup', 'minutes' => 120],
                        ['method' => 'popup', 'minutes' => 30],
                    ],
                ]
                && str_contains($request->url(), 'sendUpdates=all');
        });

        $connection->update(['calendar_id' => 'calendario-nuevo@example.com']);
        $reserva->update(['observaciones' => 'Cambio controlado']);
        (new SyncReservationToGoogleCalendar($reserva->id))
            ->handle(app(GoogleCalendarGateway::class));

        $this->assertDatabaseCount('google_calendar_events', 1);
        $this->assertDatabaseHas('google_calendar_events', [
            'reserva_id' => $reserva->id,
            'calendar_id' => 'primary',
            'google_event_id' => 'fake-google-event-id',
        ]);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && str_contains($request->url(), '/calendars/primary/events/fake-google-event-id')
            && str_contains($request->data()['description'], 'Cambio controlado'));
    }

    public function test_calendar_authorization_url_does_not_make_http_requests(): void
    {
        config()->set('services.google_calendar', [
            'client_id' => 'calendar-client-id',
            'client_secret' => 'calendar-client-secret',
            'redirect' => 'http://localhost/calendar/callback',
            'enabled' => false,
            'calendar_id' => 'primary',
        ]);
        Http::preventStrayRequests();

        $url = app(GoogleCalendarService::class)->authorizationUrl('secure-state');

        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $url);
        $this->assertStringContainsString('secure-state', $url);
        $this->assertStringContainsString('openid', urldecode($url));
        $this->assertStringContainsString('email', urldecode($url));
        $this->assertStringContainsString('calendar.events', urldecode($url));
        Http::assertNothingSent();
    }

    public function test_una_reserva_pendiente_no_genera_evento_ni_llamada(): void
    {
        config()->set('services.google_calendar', [
            'client_id' => 'calendar-client-id',
            'client_secret' => 'calendar-client-secret',
            'redirect' => 'http://localhost/calendar/callback',
            'enabled' => true,
            'calendar_id' => 'primary',
        ]);
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
        GoogleCalendarConnection::create([
            'connected_by' => $admin->id,
            'calendar_id' => 'primary',
            'access_token' => 'fake-access-token',
            'refresh_token' => 'fake-refresh-token',
            'token_expires_at' => now()->addHour(),
            'enabled' => true,
        ]);
        $reserva = Reserva::factory()->create();

        Http::preventStrayRequests();
        (new SyncReservationToGoogleCalendar($reserva->id))->handle(app(GoogleCalendarGateway::class));

        $this->assertDatabaseCount('google_calendar_events', 0);
        Http::assertNothingSent();
    }

    public function test_cancelar_conserva_y_actualiza_el_mismo_evento(): void
    {
        config()->set('services.google_calendar', [
            'client_id' => 'calendar-client-id',
            'client_secret' => 'calendar-client-secret',
            'redirect' => 'http://localhost/calendar/callback',
            'enabled' => true,
            'calendar_id' => 'primary',
        ]);
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
        GoogleCalendarConnection::create([
            'connected_by' => $admin->id,
            'calendar_id' => 'primary',
            'access_token' => 'fake-access-token',
            'refresh_token' => 'fake-refresh-token',
            'token_expires_at' => now()->addHour(),
            'enabled' => true,
        ]);
        $reserva = Reserva::factory()->cancelada()->create();
        $link = GoogleCalendarEvent::create([
            'reserva_id' => $reserva->id,
            'calendar_id' => 'primary',
            'google_event_id' => 'evento-estable',
            'sync_status' => GoogleCalendarEvent::STATUS_PENDING,
            'synced_at' => now(),
        ]);
        Http::fake([
            'https://www.googleapis.com/calendar/v3/*' => Http::response([
                'id' => 'evento-estable',
                'htmlLink' => 'https://calendar.google.com/evento-estable',
                'etag' => 'etag-cancelado',
            ]),
        ]);

        (new SyncReservationToGoogleCalendar($reserva->id))->handle(app(GoogleCalendarGateway::class));

        $this->assertSame('evento-estable', $link->fresh()->google_event_id);
        $this->assertSame(GoogleCalendarEvent::STATUS_CANCELLED, $link->fresh()->sync_status);
        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && str_contains($request->url(), '/events/evento-estable')
            && str_starts_with($request->data()['summary'], '[CANCELADA]'));
    }

    public function test_completar_conserva_y_actualiza_el_mismo_evento(): void
    {
        config()->set('services.google_calendar', [
            'client_id' => 'calendar-client-id',
            'client_secret' => 'calendar-client-secret',
            'redirect' => 'http://localhost/calendar/callback',
            'enabled' => true,
            'calendar_id' => 'primary',
        ]);
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
        GoogleCalendarConnection::create([
            'connected_by' => $admin->id,
            'calendar_id' => 'primary',
            'access_token' => 'fake-access-token',
            'refresh_token' => 'fake-refresh-token',
            'token_expires_at' => now()->addHour(),
            'enabled' => true,
        ]);
        $reserva = Reserva::factory()->create(['estado' => Reserva::ESTADO_COMPLETADA]);
        $link = GoogleCalendarEvent::create([
            'reserva_id' => $reserva->id,
            'calendar_id' => 'primary',
            'google_event_id' => 'evento-completado',
            'sync_status' => GoogleCalendarEvent::STATUS_PENDING,
            'synced_at' => now(),
        ]);
        Http::fake([
            'https://www.googleapis.com/calendar/v3/*' => Http::response([
                'id' => 'evento-completado',
                'etag' => 'etag-completado',
            ]),
        ]);

        (new SyncReservationToGoogleCalendar($reserva->id))->handle(app(GoogleCalendarGateway::class));

        $this->assertSame('evento-completado', $link->fresh()->google_event_id);
        $this->assertSame(GoogleCalendarEvent::STATUS_SYNCED, $link->fresh()->sync_status);
        Http::assertSent(fn ($request) => str_starts_with($request->data()['summary'], '[COMPLETADA]'));
    }

    public function test_la_confirmacion_prepara_un_vinculo_y_encola_la_sincronizacion(): void
    {
        config()->set('services.google_calendar.enabled', true);
        config()->set('services.google_calendar.calendar_id', 'primary');
        Queue::fake();
        $reserva = Reserva::factory()->create();

        app(ReservaService::class)->cambiarEstado($reserva, Reserva::ESTADO_CONFIRMADA);

        $this->assertDatabaseHas('google_calendar_events', [
            'reserva_id' => $reserva->id,
            'calendar_id' => 'primary',
            'sync_status' => GoogleCalendarEvent::STATUS_PENDING,
        ]);
        Queue::assertPushed(SyncReservationToGoogleCalendar::class, 1);
    }

    public function test_calendar_renueva_un_token_vencido_antes_de_sincronizar(): void
    {
        config()->set('services.google_calendar', [
            'client_id' => 'calendar-client-id',
            'client_secret' => 'calendar-client-secret',
            'redirect' => 'http://localhost/calendar/callback',
            'enabled' => true,
            'calendar_id' => 'primary',
        ]);
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
        $connection = GoogleCalendarConnection::create([
            'connected_by' => $admin->id,
            'calendar_id' => 'primary',
            'access_token' => 'token-vencido',
            'refresh_token' => 'refresh-valido',
            'token_expires_at' => now()->subMinute(),
            'enabled' => true,
        ]);
        $reserva = Reserva::factory()->confirmada()->create();
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'token-renovado',
                'expires_in' => 3600,
            ]),
            'https://www.googleapis.com/calendar/v3/*' => Http::response(['id' => 'evento-renovado']),
        ]);

        (new SyncReservationToGoogleCalendar($reserva->id))->handle(app(GoogleCalendarGateway::class));

        $this->assertSame('token-renovado', $connection->fresh()->access_token);
        $this->assertDatabaseHas('google_calendar_events', [
            'reserva_id' => $reserva->id,
            'google_event_id' => 'evento-renovado',
            'sync_status' => GoogleCalendarEvent::STATUS_SYNCED,
        ]);
        Http::assertSentCount(2);
    }

    public function test_calendar_registra_el_error_sin_perder_la_reserva_local(): void
    {
        config()->set('services.google_calendar', [
            'client_id' => 'calendar-client-id',
            'client_secret' => 'calendar-client-secret',
            'redirect' => 'http://localhost/calendar/callback',
            'enabled' => true,
            'calendar_id' => 'primary',
        ]);
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
        GoogleCalendarConnection::create([
            'connected_by' => $admin->id,
            'calendar_id' => 'primary',
            'access_token' => 'token-sin-permiso',
            'refresh_token' => 'refresh-valido',
            'token_expires_at' => now()->addHour(),
            'enabled' => true,
        ]);
        $reserva = Reserva::factory()->confirmada()->create();
        Http::fake([
            'https://www.googleapis.com/calendar/v3/*' => Http::response([
                'error' => ['message' => 'Permiso denegado'],
            ], 403),
        ]);

        try {
            (new SyncReservationToGoogleCalendar($reserva->id))->handle(app(GoogleCalendarGateway::class));
            $this->fail('La sincronización debía propagar el error para permitir el reintento de la cola.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Permiso denegado', $exception->getMessage());
        }

        $this->assertDatabaseHas('reservas', ['id' => $reserva->id, 'estado' => Reserva::ESTADO_CONFIRMADA]);
        $this->assertDatabaseHas('google_calendar_events', [
            'reserva_id' => $reserva->id,
            'sync_status' => GoogleCalendarEvent::STATUS_ERROR,
        ]);
    }
}
