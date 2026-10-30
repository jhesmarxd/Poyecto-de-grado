<?php

namespace Tests\Feature;

use App\Models\Balon;
use App\Models\Cancha;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReservaTest extends TestCase
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

    private function cliente(): User
    {
        return User::factory()->create(['rol' => User::ROL_CLIENTE]);
    }

    private function admin(): User
    {
        return User::factory()->create(['rol' => User::ROL_ADMIN]);
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

    public function test_la_consulta_de_disponibilidad_muestra_las_franjas_libres(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);

        $response = $this->get('/disponibilidad?cancha_id='.$cancha->id.'&fecha='.$fecha);

        $response->assertOk();
        $response->assertSee('Horas libres para el');
        $response->assertSee('18:00');
    }

    public function test_las_franjas_reservadas_no_aparecen_como_disponibles(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);

        Reserva::factory()->create([
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
        ]);

        $libres = $cancha->horariosDisponiblesPara($fecha);

        $this->assertTrue($libres->doesntContain(fn ($h) => $h->id === $horario->id));
    }

    public function test_un_cliente_puede_crear_una_reserva(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);

        $balon = Balon::create([
            'tipo' => Balon::TIPO_ECONOMICO,
            'marca' => 'Asatex',
            'nombre' => '1',
            'precio_por_hora' => 25.00,
        ]);

        $this->actingAs($this->cliente())
            ->post('/reservas', [
                'cancha_id' => $cancha->id,
                'horario_id' => $horario->id,
                'fecha' => $fecha,
                'balon_id' => $balon->id,
                'observaciones' => 'Partido amistoso',
            ])
            ->assertRedirect(route('reservas.mis'));

        $this->assertDatabaseHas('reservas', [
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'estado' => Reserva::ESTADO_PENDIENTE,
            'tipo_balon' => Reserva::BALON_PRESTADO,
            'balon_id' => $balon->id,
            'monto_total' => 25.00,
        ]);

        $this->assertSame(
            $fecha,
            Reserva::first()->fecha->toDateString()
        );
    }

    public function test_se_puede_reservar_trayendo_el_propio_balon(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);

        $this->actingAs($this->cliente())
            ->post('/reservas', [
                'cancha_id' => $cancha->id,
                'horario_id' => $horario->id,
                'fecha' => $fecha,
            ])
            ->assertRedirect(route('reservas.mis'));

        $this->assertDatabaseHas('reservas', [
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'tipo_balon' => Reserva::BALON_PROPIO,
            'balon_id' => null,
            'monto_total' => $cancha->precio_por_hora,
        ]);
    }

    public function test_el_balon_elegido_debe_existir(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);

        $this->actingAs($this->cliente())
            ->post('/reservas', [
                'cancha_id' => $cancha->id,
                'horario_id' => $horario->id,
                'fecha' => $fecha,
                'balon_id' => 999999,
            ])
            ->assertSessionHasErrors('balon_id');

        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_el_balon_premium_aplica_el_precio_del_balon_elegido(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);

        $balon = Balon::create([
            'tipo' => Balon::TIPO_PREMIUM,
            'marca' => 'Mikasa',
            'nombre' => 'V200W',
            'precio_por_hora' => 30.00,
        ]);

        $this->actingAs($this->cliente())
            ->post('/reservas', [
                'cancha_id' => $cancha->id,
                'horario_id' => $horario->id,
                'fecha' => $fecha,
                'balon_id' => $balon->id,
            ])
            ->assertRedirect(route('reservas.mis'));

        $this->assertDatabaseHas('reservas', [
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'tipo_balon' => Reserva::BALON_PREMIUM,
            'balon_id' => $balon->id,
            'monto_total' => 30.00,
        ]);
    }

    public function test_un_balon_ya_reservado_ese_dia_y_hora_no_puede_elegirse_en_otra_cancha(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);
        $diaSemana = Carbon::parse($fecha)->dayOfWeekIso;

        $balon = Balon::create([
            'tipo' => Balon::TIPO_ECONOMICO,
            'marca' => 'Veloz',
            'nombre' => '1',
            'precio_por_hora' => 25.00,
        ]);

        Reserva::factory()->confirmada()->create([
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
            'balon_id' => $balon->id,
            'tipo_balon' => Reserva::BALON_PRESTADO,
            'monto_total' => 25.00,
        ]);

        // Otra cancha con una franja a la misma hora del mismo día
        $cancha2 = Cancha::factory()->create();
        $horario2 = Horario::factory()->create([
            'cancha_id' => $cancha2->id,
            'dia_semana' => $diaSemana,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
        ]);

        $this->actingAs($this->cliente())
            ->post('/reservas', [
                'cancha_id' => $cancha2->id,
                'horario_id' => $horario2->id,
                'fecha' => $fecha,
                'balon_id' => $balon->id,
            ])
            ->assertStatus(409);

        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_un_balon_distinto_si_puede_reservarse_en_la_misma_franja(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);
        $diaSemana = Carbon::parse($fecha)->dayOfWeekIso;

        $balon = Balon::create([
            'tipo' => Balon::TIPO_ECONOMICO,
            'marca' => 'Veloz',
            'nombre' => '1',
            'precio_por_hora' => 25.00,
        ]);

        Reserva::factory()->confirmada()->create([
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
            'balon_id' => $balon->id,
            'tipo_balon' => Reserva::BALON_PRESTADO,
            'monto_total' => 25.00,
        ]);

        $balon2 = Balon::create([
            'tipo' => Balon::TIPO_ECONOMICO,
            'marca' => 'Veloz',
            'nombre' => '2',
            'precio_por_hora' => 25.00,
        ]);

        $cancha2 = Cancha::factory()->create();
        $horario2 = Horario::factory()->create([
            'cancha_id' => $cancha2->id,
            'dia_semana' => $diaSemana,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
        ]);

        $this->actingAs($this->cliente())
            ->post('/reservas', [
                'cancha_id' => $cancha2->id,
                'horario_id' => $horario2->id,
                'fecha' => $fecha,
                'balon_id' => $balon2->id,
            ])
            ->assertRedirect(route('reservas.mis'));

        $this->assertDatabaseHas('reservas', [
            'cancha_id' => $cancha2->id,
            'horario_id' => $horario2->id,
            'balon_id' => $balon2->id,
            'monto_total' => 25.00,
        ]);
    }

    public function test_no_se_puede_reservar_una_franja_ya_ocupada(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);

        Reserva::factory()->create([
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
        ]);

        $this->actingAs($this->cliente())
            ->post('/reservas', [
                'cancha_id' => $cancha->id,
                'horario_id' => $horario->id,
                'fecha' => $fecha,
            ])
            ->assertStatus(409);

        $this->assertDatabaseCount('reservas', 1);
    }

    public function test_un_horario_no_puede_reservarse_dos_veces_a_nivel_de_bd(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);
        $usuario = $this->cliente();

        Reserva::create([
            'user_id' => $usuario->id,
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
            'monto_total' => $cancha->precio_por_hora,
            'estado' => Reserva::ESTADO_PENDIENTE,
        ]);

        // El índice único de la BD rechaza el duplicado
        $this->expectException(QueryException::class);
        Reserva::create([
            'user_id' => $usuario->id,
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
            'monto_total' => $cancha->precio_por_hora,
            'estado' => Reserva::ESTADO_CONFIRMADA,
        ]);
    }

    public function test_no_se_puede_reservar_en_el_pasado(): void
    {
        $fecha = now()->subDays(1)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario(now()->addDays(3)->toDateString());

        $this->actingAs($this->cliente())
            ->post('/reservas', [
                'cancha_id' => $cancha->id,
                'horario_id' => $horario->id,
                'fecha' => $fecha,
            ])
            ->assertSessionHasErrors('fecha');

        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_la_fecha_debe_coincidir_con_el_dia_del_horario(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);
        // Una fecha que cae en otro día de la semana
        $otraFecha = now()->addDays(10)->toDateString();

        while (Carbon::parse($otraFecha)->dayOfWeekIso === Carbon::parse($fecha)->dayOfWeekIso) {
            $otraFecha = Carbon::parse($otraFecha)->addDay()->toDateString();
        }

        $this->actingAs($this->cliente())
            ->post('/reservas', [
                'cancha_id' => $cancha->id,
                'horario_id' => $horario->id,
                'fecha' => $otraFecha,
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_un_cliente_ve_sus_propias_reservas(): void
    {
        $usuario = $this->cliente();
        [$cancha, $horario] = $this->canchaConHorario(now()->addDays(3)->toDateString());

        Reserva::factory()->create([
            'user_id' => $usuario->id,
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
        ]);

        $this->actingAs($usuario)
            ->get('/mis-reservas?vista=listado')
            ->assertOk()
            ->assertSee($cancha->nombre)
            ->assertSee('Pendiente');
    }

    public function test_un_cliente_puede_cancelar_su_propia_reserva_pendiente(): void
    {
        $usuario = $this->cliente();
        [$cancha, $horario] = $this->canchaConHorario(now()->addDays(3)->toDateString());

        $reserva = Reserva::factory()->create([
            'user_id' => $usuario->id,
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
        ]);

        $this->actingAs($usuario)
            ->post("/reservas/{$reserva->id}/cancelar")
            ->assertRedirect(route('reservas.mis'));

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'estado' => Reserva::ESTADO_CANCELADA,
        ]);

        // La franja vuelve a estar disponible
        $this->assertTrue(
            $cancha->horariosDisponiblesPara($reserva->fecha->toDateString())
                ->contains(fn ($h) => $h->id === $horario->id)
        );
    }

    public function test_un_cliente_no_puede_cancelar_la_reserva_de_otro(): void
    {
        $otro = $this->cliente();
        [$cancha, $horario] = $this->canchaConHorario(now()->addDays(3)->toDateString());

        $reserva = Reserva::factory()->create([
            'user_id' => $otro->id,
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
        ]);

        $this->actingAs($this->cliente())
            ->post("/reservas/{$reserva->id}/cancelar")
            ->assertForbidden();

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'estado' => Reserva::ESTADO_PENDIENTE,
        ]);
    }

    public function test_no_se_puede_cancelar_una_reserva_ya_cancelada(): void
    {
        $usuario = $this->cliente();
        [$cancha, $horario] = $this->canchaConHorario(now()->addDays(3)->toDateString());

        $reserva = Reserva::factory()->cancelada()->create([
            'user_id' => $usuario->id,
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
        ]);

        $this->actingAs($usuario)
            ->post("/reservas/{$reserva->id}/cancelar")
            ->assertStatus(422);
    }

    public function test_un_admin_puede_cambiar_el_estado_de_una_reserva(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);

        $reserva = Reserva::factory()->create([
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
        ]);

        $this->actingAs($this->admin())
            ->post("/admin/reservas/{$reserva->id}/estado", ['estado' => Reserva::ESTADO_CONFIRMADA])
            ->assertRedirect();

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'estado' => Reserva::ESTADO_CONFIRMADA,
        ]);
    }

    public function test_un_cliente_no_puede_gestionar_reservas_en_el_panel_admin(): void
    {
        $this->actingAs($this->cliente())
            ->get('/admin/reservas')
            ->assertForbidden();

        $this->actingAs($this->cliente())
            ->get('/admin/reservas/calendario')
            ->assertForbidden();
    }

    public function test_el_endpoint_de_eventos_devuelve_las_reservas_del_rango(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConHorario($fecha);

        Reserva::factory()->create([
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
        ]);

        $response = $this->actingAs($this->admin())
            ->getJson('/admin/reservas/eventos?start='.now()->startOfMonth()->toDateString().'&end='.now()->endOfMonth()->toDateString());

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['cancha' => $cancha->nombre]);
    }

    public function test_el_endpoint_de_eventos_requiere_ser_administrador(): void
    {
        $this->actingAs($this->cliente())
            ->getJson('/admin/reservas/eventos')
            ->assertForbidden();
    }
}
