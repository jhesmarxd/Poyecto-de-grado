<?php

namespace Tests\Feature;

use App\Models\Cancha;
use App\Models\GoogleCalendarEvent;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReservaCalendarTest extends TestCase
{
    use RefreshDatabase;

    private function reserva(User $cliente, string $fecha = '2026-10-10', array $atributos = []): Reserva
    {
        $cancha = $atributos['cancha'] ?? Cancha::factory()->create(['nombre' => 'Cancha Central']);
        $horario = Horario::factory()->create([
            'cancha_id' => $cancha->id,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
        ]);

        unset($atributos['cancha']);

        return Reserva::factory()->create(array_merge([
            'user_id' => $cliente->id,
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
            'estado' => Reserva::ESTADO_CONFIRMADA,
            'monto_total' => 85,
            'observaciones' => 'Reserva de prueba',
        ], $atributos));
    }

    private function rango(string $start = '2026-10-01', string $end = '2026-11-01'): array
    {
        return ['start' => $start, 'end' => $end];
    }

    public function test_los_tres_roles_autorizados_pueden_abrir_su_calendario(): void
    {
        Http::preventStrayRequests();
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
        $recepcionista = User::factory()->create(['rol' => User::ROL_RECEPCIONISTA]);
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);

        $this->actingAs($admin)->get(route('admin.reservas.calendario'))
            ->assertOk()->assertSee('data-role="administrador"', false);
        $this->actingAs($recepcionista)->get(route('recepcionista.reservas.calendario'))
            ->assertOk()->assertSee('data-role="recepcionista"', false);
        $this->actingAs($cliente)->get(route('reservas.mis'))
            ->assertOk()->assertSee('data-role="cliente"', false)->assertSee('Listado');
    }

    public function test_el_administrador_recibe_el_detalle_completo_sin_estado_google(): void
    {
        Http::preventStrayRequests();
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
        $cliente = User::factory()->create([
            'rol' => User::ROL_CLIENTE,
            'name' => 'Cliente Agenda',
            'email' => 'agenda@example.com',
            'telefono' => '70000001',
        ]);
        $reserva = $this->reserva($cliente);
        $response = $this->actingAs($admin)->getJson(route('admin.reservas.eventos', $this->rango()));

        $response->assertOk()
            ->assertJsonPath('0.extendedProps.cliente', 'Cliente Agenda')
            ->assertJsonPath('0.extendedProps.correo', 'agenda@example.com')
            ->assertJsonPath('0.extendedProps.telefono', '70000001')
            ->assertJsonPath('0.extendedProps.monto', 'Bs. 85.00');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringEndsWith('-04:00', $response->json('0.start'));
    }

    public function test_recepcion_solo_recibe_los_campos_operativos_permitidos(): void
    {
        $recepcionista = User::factory()->create(['rol' => User::ROL_RECEPCIONISTA]);
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE, 'telefono' => '70000002']);
        $this->reserva($cliente);

        $response = $this->actingAs($recepcionista)
            ->getJson(route('recepcionista.reservas.eventos', $this->rango()));

        $response->assertOk()->assertJsonPath('0.extendedProps.telefono', '70000002');
        $props = $response->json('0.extendedProps');
        $this->assertSame(
            ['estado', 'cliente', 'telefono', 'cancha', 'fecha', 'hora_inicio', 'hora_fin', 'balon', 'check_in', 'check_out'],
            array_keys($props)
        );
    }

    public function test_el_cliente_recibe_solo_sus_reservas_y_campos_publicos(): void
    {
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);
        $otro = User::factory()->create(['rol' => User::ROL_CLIENTE]);
        $propia = $this->reserva($cliente);
        GoogleCalendarEvent::create([
            'reserva_id' => $propia->id,
            'calendar_id' => 'primary',
            'google_event_id' => 'evento-propio',
            'google_event_url' => 'https://calendar.google.com/calendar/event?eid=propia',
            'sync_status' => GoogleCalendarEvent::STATUS_SYNCED,
        ]);
        $this->reserva($otro, '2026-10-11');

        $response = $this->actingAs($cliente)->getJson(route('reservas.mis.eventos', $this->rango()));

        $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', (string) $propia->id);
        $this->assertSame(
            ['estado', 'cancha', 'fecha', 'hora_inicio', 'hora_fin', 'google_estado', 'google_url'],
            array_keys($response->json('0.extendedProps'))
        );
        $response->assertJsonPath('0.extendedProps.google_estado', 'Agendada en Google Calendar')
            ->assertJsonPath('0.extendedProps.google_url', 'https://calendar.google.com/calendar/event?eid=propia');
    }

    public function test_el_titulo_del_cliente_muestra_el_intervalo_real_completo(): void
    {
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);
        $this->reserva($cliente, '2026-10-10', [
            'hora_inicio_reserva' => '19:00:00',
            'hora_fin_reserva' => '21:00:00',
        ]);

        $this->actingAs($cliente)
            ->getJson(route('reservas.mis.eventos', $this->rango()))
            ->assertOk()
            ->assertJsonPath('0.title', '19:00–21:00 | Cancha Central')
            ->assertJsonPath('0.extendedProps.hora_fin', '21:00');
    }

    public function test_el_fin_del_rango_es_exclusivo_y_los_filtros_se_aplican(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);
        $cancha = Cancha::factory()->create();
        $incluida = $this->reserva($cliente, '2026-10-01', [
            'cancha' => $cancha,
            'estado' => Reserva::ESTADO_PENDIENTE,
        ]);
        $this->reserva($cliente, '2026-10-02', ['estado' => Reserva::ESTADO_CONFIRMADA]);

        $response = $this->actingAs($admin)->getJson(route('admin.reservas.eventos', [
            'start' => '2026-10-01',
            'end' => '2026-10-02',
            'cancha_id' => $cancha->id,
            'estado' => Reserva::ESTADO_PENDIENTE,
        ]));

        $response->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', (string) $incluida->id);
    }

    public function test_cada_estado_utiliza_su_color_y_clase_visual(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);
        $estados = [
            Reserva::ESTADO_PENDIENTE,
            Reserva::ESTADO_CONFIRMADA,
            Reserva::ESTADO_CANCELADA,
            Reserva::ESTADO_COMPLETADA,
        ];

        foreach ($estados as $indice => $estado) {
            $this->reserva($cliente, '2026-10-'.str_pad((string) (10 + $indice), 2, '0', STR_PAD_LEFT), [
                'estado' => $estado,
            ]);
        }

        $eventos = $this->actingAs($admin)
            ->getJson(route('admin.reservas.eventos', $this->rango()))
            ->assertOk()
            ->json();

        foreach ($estados as $estado) {
            $evento = collect($eventos)->first(fn (array $item) => in_array('reserva-estado-'.$estado, $item['classNames'], true));
            $this->assertNotNull($evento, "No se encontró el evento con estado {$estado}.");
            $this->assertSame(Reserva::COLORES[$estado], $evento['backgroundColor']);
        }
    }

    public function test_el_rango_y_los_filtros_invalidos_son_rechazados(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);

        $this->actingAs($admin)->getJson(route('admin.reservas.eventos', [
            'start' => '2025-01-01',
            'end' => '2026-12-31',
            'estado' => 'inventado',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['end', 'estado']);
    }

    public function test_los_roles_no_pueden_cruzar_los_limites_de_acceso(): void
    {
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);
        $recepcionista = User::factory()->create(['rol' => User::ROL_RECEPCIONISTA]);

        $this->getJson(route('reservas.mis.eventos', $this->rango()))->assertUnauthorized();
        $this->actingAs($cliente)->getJson(route('admin.reservas.eventos', $this->rango()))->assertForbidden();
        $this->actingAs($cliente)->getJson(route('recepcionista.reservas.eventos', $this->rango()))->assertForbidden();
        $this->actingAs($recepcionista)->getJson(route('reservas.mis.eventos', $this->rango()))->assertForbidden();
    }

    public function test_el_modal_del_calendario_inserta_datos_como_texto_y_no_como_html(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
        $cliente = User::factory()->create([
            'rol' => User::ROL_CLIENTE,
            'name' => '<img src=x onerror=alert(1)>',
        ]);
        $this->reserva($cliente, '2026-10-10', ['observaciones' => '<script>alert(1)</script>']);

        $this->actingAs($admin)
            ->getJson(route('admin.reservas.eventos', $this->rango()))
            ->assertOk()
            ->assertJsonPath('0.extendedProps.cliente', '<img src=x onerror=alert(1)>')
            ->assertJsonPath('0.extendedProps.observaciones', '<script>alert(1)</script>');

        $javascript = file_get_contents(resource_path('js/reservation-calendar.js'));

        $this->assertStringContainsString('description.textContent = value', $javascript);
        $this->assertStringContainsString('term.textContent = label', $javascript);
        $this->assertStringNotContainsString('innerHTML', $javascript);
    }
}
