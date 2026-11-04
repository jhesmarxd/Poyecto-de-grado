<?php

namespace Tests\Feature;

use App\Models\Cancha;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecepcionistaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 5, 10, 0, 0, 'America/La_Paz'));
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function recepcionista(): User
    {
        return User::factory()->create(['rol' => 'recepcionista']);
    }

    private function admin(): User
    {
        return User::factory()->create(['rol' => User::ROL_ADMIN]);
    }

    private function cliente(): User
    {
        return User::factory()->create(['rol' => User::ROL_CLIENTE, 'telefono' => '71234567']);
    }

    private function canchaConHorario(string $fecha): array
    {
        $cancha = Cancha::factory()->create();
        $diaSemana = Carbon::parse($fecha)->dayOfWeekIso;
        $horario = Horario::factory()->create([
            'cancha_id' => $cancha->id,
            'dia_semana' => $diaSemana,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
        ]);

        return [$cancha, $horario];
    }

    // === Pruebas de acceso ===

    public function test_un_recepcionista_puede_acceder_al_panel(): void
    {
        $this->actingAs($this->recepcionista())
            ->get('/recepcionista/reservas/hoy')
            ->assertOk();
    }

    public function test_un_admin_puede_acceder_al_panel_de_recepcionista(): void
    {
        $this->actingAs($this->admin())
            ->get('/recepcionista/reservas/hoy')
            ->assertOk();
    }

    public function test_un_cliente_no_puede_acceder_al_panel_de_recepcionista(): void
    {
        $this->actingAs($this->cliente())
            ->get('/recepcionista/reservas/hoy')
            ->assertForbidden();
    }

    public function test_un_usuario_no_autenticado_no_puede_acceder(): void
    {
        $this->get('/recepcionista/reservas/hoy')
            ->assertRedirect('/login');
    }

    // === Pruebas de reservas presenciales ===

    public function test_el_recepcionista_puede_ver_reservas_del_dia(): void
    {
        $fecha = now()->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);

        Reserva::factory()->create([
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
        ]);

        $this->actingAs($this->recepcionista())
            ->get('/recepcionista/reservas/hoy')
            ->assertOk()
            ->assertSee($cancha->nombre)
            ->assertDontSee('Por confirmar');
    }

    public function test_el_recepcionista_puede_crear_reserva_presencial(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);
        $cliente = $this->cliente();

        $this->actingAs($this->recepcionista())
            ->post('/recepcionista/reservas', [
                'user_id' => $cliente->id,
                'cancha_id' => $cancha->id,
                'horario_id' => $horario->id,
                'fecha' => $fecha,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reservas', [
            'user_id' => $cliente->id,
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'estado' => Reserva::ESTADO_CONFIRMADA,
        ]);
    }

    public function test_el_formulario_de_reserva_carga_la_disponibilidad_dinamica(): void
    {
        $clienteActivo = $this->cliente();
        $clienteInactivo = User::factory()->create([
            'rol' => User::ROL_CLIENTE,
            'estado' => false,
        ]);

        $this->actingAs($this->recepcionista())
            ->get(route('recepcionista.reservas.create'))
            ->assertOk()
            ->assertSee($clienteActivo->name)
            ->assertDontSee($clienteInactivo->name)
            ->assertSee('Se mostrarán únicamente las franjas disponibles.');
    }

    public function test_el_formulario_devuelve_solo_horarios_disponibles(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horarioOcupado] = $this->canchaConHorario($fecha);
        $horarioLibre = Horario::factory()->create([
            'cancha_id' => $cancha->id,
            'dia_semana' => Carbon::parse($fecha)->dayOfWeekIso,
            'hora_inicio' => '19:00:00',
            'hora_fin' => '20:00:00',
        ]);

        Reserva::factory()->create([
            'cancha_id' => $cancha->id,
            'horario_id' => $horarioOcupado->id,
            'fecha' => $fecha,
        ]);

        $this->actingAs($this->recepcionista())
            ->getJson(route('recepcionista.reservas.horarios-disponibles', [
                'cancha_id' => $cancha->id,
                'fecha' => $fecha,
            ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $horarioLibre->id]);
    }

    public function test_no_se_puede_crear_una_reserva_para_un_usuario_inactivo(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);
        $cliente = User::factory()->create([
            'rol' => User::ROL_CLIENTE,
            'estado' => false,
        ]);

        $this->actingAs($this->recepcionista())
            ->post(route('recepcionista.reservas.store'), [
                'user_id' => $cliente->id,
                'cancha_id' => $cancha->id,
                'horario_id' => $horario->id,
                'fecha' => $fecha,
            ])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_el_recepcionista_puede_cambiar_estado_de_reserva(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);

        $reserva = Reserva::factory()->create([
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
        ]);

        $this->actingAs($this->recepcionista())
            ->post("/recepcionista/reservas/{$reserva->id}/estado", [
                'estado' => Reserva::ESTADO_CONFIRMADA,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'estado' => Reserva::ESTADO_CONFIRMADA,
        ]);
    }

    public function test_el_historial_busca_y_pagina_reservas(): void
    {
        $clienteBuscado = User::factory()->create([
            'rol' => User::ROL_CLIENTE,
            'name' => 'Cliente Historial Único',
        ]);

        Reserva::factory()->create(['user_id' => $clienteBuscado->id]);
        Reserva::factory()->count(26)->create();

        $this->actingAs($this->recepcionista())
            ->get(route('recepcionista.reservas.index'))
            ->assertOk()
            ->assertViewHas('reservas', fn ($reservas) => $reservas->total() === 27
                && $reservas->count() === 25);

        $this->actingAs($this->recepcionista())
            ->get(route('recepcionista.reservas.index', ['q' => 'Historial Único']))
            ->assertOk()
            ->assertSee($clienteBuscado->name)
            ->assertSee('1 reserva encontrada');
    }

    // === Pruebas de check-in/check-out ===

    public function test_el_recepcionista_puede_ver_checkin(): void
    {
        $this->actingAs($this->recepcionista())
            ->get('/recepcionista/checkin')
            ->assertRedirect(route('recepcionista.reservas.hoy', ['accion' => 'checkin']));
    }

    public function test_el_recepcionista_puede_realizar_checkin(): void
    {
        $fecha = now()->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);

        $reserva = Reserva::factory()->confirmada()->create([
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
        ]);

        $this->actingAs($this->recepcionista())
            ->post("/recepcionista/checkin/{$reserva->id}")
            ->assertRedirect(route('recepcionista.reservas.hoy'));

        $reserva->refresh();

        $this->assertSame(Reserva::ESTADO_CONFIRMADA, $reserva->estado);
        $this->assertNotNull($reserva->check_in_at);
        $this->assertNull($reserva->check_out_at);
    }

    public function test_el_recepcionista_puede_ver_checkout(): void
    {
        $this->actingAs($this->recepcionista())
            ->get('/recepcionista/checkout')
            ->assertRedirect(route('recepcionista.reservas.hoy', ['accion' => 'checkout']));
    }

    public function test_el_recepcionista_puede_realizar_checkout(): void
    {
        $fecha = now()->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);

        $reserva = Reserva::factory()->confirmada()->create([
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
            'check_in_at' => now()->subMinutes(50),
        ]);

        $this->actingAs($this->recepcionista())
            ->post("/recepcionista/checkout/{$reserva->id}")
            ->assertRedirect(route('recepcionista.reservas.hoy'));

        $reserva->refresh();

        $this->assertSame(Reserva::ESTADO_COMPLETADA, $reserva->estado);
        $this->assertNotNull($reserva->check_out_at);
    }

    public function test_no_se_puede_registrar_salida_sin_entrada_previa(): void
    {
        $fecha = now()->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);

        $reserva = Reserva::factory()->confirmada()->create([
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
        ]);

        $this->actingAs($this->recepcionista())
            ->post("/recepcionista/checkout/{$reserva->id}")
            ->assertUnprocessable();
    }

    // === Pruebas de búsqueda de clientes ===

    public function test_el_recepcionista_puede_buscar_clientes(): void
    {
        $cliente = $this->cliente();

        $this->actingAs($this->recepcionista())
            ->get('/recepcionista/clientes/buscar?q='.$cliente->name)
            ->assertOk()
            ->assertSee($cliente->name);
    }

    public function test_el_autocomplete_devuelve_clientes(): void
    {
        $cliente = $this->cliente();

        $response = $this->actingAs($this->recepcionista())
            ->getJson('/recepcionista/clientes/autocomplete?q='.$cliente->name);

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => $cliente->name]);
    }

    public function test_el_autocomplete_no_devuelve_resultados_con_menos_de_2_caracteres(): void
    {
        $response = $this->actingAs($this->recepcionista())
            ->getJson('/recepcionista/clientes/autocomplete?q=a');

        $response->assertOk();
        $response->assertJsonCount(0);
    }

    // === Pruebas de autorización ===

    public function test_un_cliente_no_puede_crear_reserva_presencial(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);
        $cliente = $this->cliente();

        $this->actingAs($cliente)
            ->post('/recepcionista/reservas', [
                'user_id' => $cliente->id,
                'cancha_id' => $cancha->id,
                'horario_id' => $horario->id,
                'fecha' => $fecha,
            ])
            ->assertForbidden();
    }

    public function test_un_cliente_no_puede_realizar_checkin(): void
    {
        $fecha = now()->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);

        $reserva = Reserva::factory()->confirmada()->create([
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
        ]);

        $this->actingAs($this->cliente())
            ->post("/recepcionista/checkin/{$reserva->id}")
            ->assertForbidden();
    }
}
