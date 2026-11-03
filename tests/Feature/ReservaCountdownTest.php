<?php

namespace Tests\Feature;

use App\Models\Cancha;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReservaCountdownTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_muestra_la_reserva_confirmada_vigente_mas_proxima_con_hora_del_servidor(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 12, 0, 0, 'America/La_Paz'));
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);

        $this->crearReserva($cliente, 'Cancha finalizada', '09:00:00', '10:00:00', Reserva::ESTADO_CONFIRMADA);
        $this->crearReserva($cliente, 'Cancha pendiente', '11:30:00', '12:30:00', Reserva::ESTADO_PENDIENTE);
        $proxima = $this->crearReserva($cliente, 'Cancha próxima', '14:00:00', '15:00:00', Reserva::ESTADO_CONFIRMADA);
        $this->crearReserva($cliente, 'Cancha posterior', '16:00:00', '17:00:00', Reserva::ESTADO_CONFIRMADA);

        $inicioEsperado = Carbon::create(2026, 9, 15, 14, 0, 0, 'America/La_Paz')->getTimestamp() * 1000;
        $finEsperado = Carbon::create(2026, 9, 15, 15, 0, 0, 'America/La_Paz')->getTimestamp() * 1000;
        $ahoraEsperado = now()->getTimestamp() * 1000;

        $this->actingAs($cliente)
            ->get(route('reservas.mis'))
            ->assertOk()
            ->assertSee('data-reservation-countdown', false)
            ->assertSee('Cancha próxima')
            ->assertSee('data-start-at="'.$inicioEsperado.'"', false)
            ->assertSee('data-end-at="'.$finEsperado.'"', false)
            ->assertSee('data-server-now="'.$ahoraEsperado.'"', false);

        $this->assertSame($inicioEsperado, $proxima->iniciaEn()->getTimestamp() * 1000);
    }

    public function test_no_muestra_temporizador_sin_una_reserva_confirmada_vigente(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 18, 0, 0, 'America/La_Paz'));
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);

        $this->crearReserva($cliente, 'Cancha completada', '19:00:00', '20:00:00', Reserva::ESTADO_COMPLETADA);
        $this->crearReserva($cliente, 'Cancha cancelada', '20:00:00', '21:00:00', Reserva::ESTADO_CANCELADA);
        $this->crearReserva($cliente, 'Cancha pasada', '15:00:00', '16:00:00', Reserva::ESTADO_CONFIRMADA);

        $this->actingAs($cliente)
            ->get(route('reservas.mis'))
            ->assertOk()
            ->assertDontSee('data-reservation-countdown', false)
            ->assertSee('No tienes próximas reservas');
    }

    public function test_prioriza_la_reserva_futura_mas_cercana_y_ordena_el_listado(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 31, 14, 0, 0, 'America/La_Paz'));
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);
        $otro = User::factory()->create(['rol' => User::ROL_CLIENTE]);

        $this->crearReserva($cliente, 'Cancha año siguiente', '20:00:00', '21:00:00', Reserva::ESTADO_CONFIRMADA, '2027-08-31');
        $this->crearReserva($cliente, 'Cancha noviembre', '19:00:00', '20:00:00', Reserva::ESTADO_CONFIRMADA, '2026-11-05');
        $cercana = $this->crearReserva($cliente, 'Cancha hoy', '17:00:00', '18:00:00', Reserva::ESTADO_PENDIENTE, '2026-10-31');
        $this->crearReserva($cliente, 'Cancha cancelada', '15:00:00', '16:00:00', Reserva::ESTADO_CANCELADA, '2026-10-31');
        $this->crearReserva($cliente, 'Cancha pasada', '11:00:00', '12:00:00', Reserva::ESTADO_CONFIRMADA, '2026-10-31');
        $this->crearReserva($otro, 'Cancha ajena', '16:00:00', '17:00:00', Reserva::ESTADO_CONFIRMADA, '2026-10-31');

        $this->actingAs($cliente)->get(route('reservas.mis', ['vista' => 'listado']))
            ->assertOk()
            ->assertSee('data-start-at="'.($cercana->iniciaEn()->getTimestamp() * 1000).'"', false)
            ->assertSeeInOrder([
                '<strong>Cancha hoy</strong>', '<strong>Cancha noviembre</strong>',
                '<strong>Cancha año siguiente</strong>', '<strong>Cancha cancelada</strong>',
                '<strong>Cancha pasada</strong>',
            ], false)
            ->assertDontSee('Cancha ajena');
    }

    public function test_listado_muestra_hoy_y_futuro_pero_no_ayer_y_el_calendario_conserva_su_historial(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 31, 14, 0, 0, 'America/La_Paz'));
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);
        $this->crearReserva($cliente, 'Cancha ayer', '18:00:00', '19:00:00', Reserva::ESTADO_COMPLETADA, '2026-10-30');
        $this->crearReserva($cliente, 'Cancha hoy terminada', '09:00:00', '10:00:00', Reserva::ESTADO_COMPLETADA, '2026-10-31');
        $this->crearReserva($cliente, 'Cancha mañana', '18:00:00', '19:00:00', Reserva::ESTADO_CONFIRMADA, '2026-11-01');

        $this->actingAs($cliente)->get(route('reservas.mis', ['vista' => 'listado']))
            ->assertOk()
            ->assertSee('<strong>Cancha hoy terminada</strong>', false)
            ->assertSee('<strong>Cancha mañana</strong>', false)
            ->assertDontSee('Cancha ayer');

        $this->getJson(route('reservas.mis.eventos', [
            'start' => '2026-10-29', 'end' => '2026-11-02',
        ]))->assertOk()->assertJsonCount(3);
        $this->assertDatabaseCount('reservas', 3);
    }

    public function test_panel_usa_la_imagen_de_la_cancha_proxima_y_cambia_con_la_reserva(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 31, 14, 0, 0, 'America/La_Paz'));
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);
        $primera = $this->crearReserva($cliente, 'Cancha 1', '17:00:00', '18:00:00', Reserva::ESTADO_CONFIRMADA, '2026-10-31');
        $segunda = $this->crearReserva($cliente, 'Cancha 2', '19:00:00', '20:00:00', Reserva::ESTADO_CONFIRMADA, '2026-10-31');
        $primera->cancha->update(['imagen' => 'cancha-1.jpg']);
        $segunda->cancha->update(['imagen' => 'cancha-2.jpg']);

        $this->actingAs($cliente)->get(route('reservas.mis'))
            ->assertOk()
            ->assertSee('img/canchas/cancha-1.jpg')
            ->assertDontSee('img/canchas/cancha-2.jpg');

        Carbon::setTestNow(Carbon::create(2026, 10, 31, 17, 0, 1, 'America/La_Paz'));
        $this->get(route('reservas.mis'))
            ->assertOk()
            ->assertSee('img/canchas/cancha-2.jpg')
            ->assertDontSee('img/canchas/cancha-1.jpg');
    }

    public function test_panel_muestra_alternativa_si_la_cancha_no_tiene_imagen(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 31, 14, 0, 0, 'America/La_Paz'));
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);
        $this->crearReserva($cliente, 'Cancha sin imagen', '17:00:00', '18:00:00', Reserva::ESTADO_CONFIRMADA, '2026-10-31');

        $this->actingAs($cliente)->get(route('reservas.mis'))
            ->assertOk()
            ->assertSee('reservation-countdown__court-image')
            ->assertDontSee('img/canchas/');
    }

    public function test_calcula_correctamente_una_franja_que_cruza_la_medianoche(): void
    {
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);
        $reserva = $this->crearReserva(
            $cliente,
            'Cancha nocturna',
            '23:30:00',
            '00:30:00',
            Reserva::ESTADO_CONFIRMADA
        );

        $this->assertSame('23:30', $reserva->iniciaEn()->format('H:i'));
        $this->assertSame('00:30', $reserva->finalizaEn()->format('H:i'));
        $this->assertTrue(
            $reserva->finalizaEn()->isSameDay($reserva->iniciaEn()->copy()->addDay())
        );
        $this->assertSame('America/La_Paz', $reserva->iniciaEn()->timezoneName);
    }

    private function crearReserva(
        User $cliente,
        string $nombreCancha,
        string $inicio,
        string $fin,
        string $estado,
        string $fecha = '2026-09-15'
    ): Reserva {
        $cancha = Cancha::factory()->create(['nombre' => $nombreCancha]);
        $horario = Horario::factory()->create([
            'cancha_id' => $cancha->id,
            'dia_semana' => 2,
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
        ]);

        return Reserva::factory()->create([
            'user_id' => $cliente->id,
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
            'estado' => $estado,
        ]);
    }
}
