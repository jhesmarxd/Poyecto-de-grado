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
        $this->crearReserva($cliente, 'Cancha pendiente', '12:30:00', '13:30:00', Reserva::ESTADO_PENDIENTE);
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

        $this->crearReserva($cliente, 'Cancha pendiente', '19:00:00', '20:00:00', Reserva::ESTADO_PENDIENTE);
        $this->crearReserva($cliente, 'Cancha cancelada', '20:00:00', '21:00:00', Reserva::ESTADO_CANCELADA);
        $this->crearReserva($cliente, 'Cancha pasada', '15:00:00', '16:00:00', Reserva::ESTADO_CONFIRMADA);

        $this->actingAs($cliente)
            ->get(route('reservas.mis'))
            ->assertOk()
            ->assertDontSee('data-reservation-countdown', false);
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
        string $estado
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
            'fecha' => '2026-09-15',
            'estado' => $estado,
        ]);
    }
}
