<?php

namespace Tests\Feature;

use App\Models\Balon;
use App\Models\Cancha;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use App\Services\PoliticaReservasService;
use Database\Seeders\CatalogoProduccionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReservasTresHorasTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function catalogo(): array
    {
        $this->seed(CatalogoProduccionSeeder::class);

        return [Cancha::where('nombre', 'Cancha 1')->firstOrFail(), Cancha::where('nombre', 'Cancha 2')->firstOrFail()];
    }

    public function test_catalogo_es_idempotente_y_domingo_esta_cerrado(): void
    {
        $this->catalogo();
        $this->seed(CatalogoProduccionSeeder::class);

        $this->assertDatabaseCount('canchas', 2);
        $this->assertDatabaseCount('balones', 8);
        $this->assertDatabaseCount('horarios', 336);
        $this->assertDatabaseCount('reservas', 0);
        $this->assertSame(0, app(PoliticaReservasService::class)
            ->franjasParaConsulta(Cancha::firstOrFail(), '2026-11-08')->count());
    }

    public function test_hoy_comienza_en_la_siguiente_hora_y_muestra_ocupados(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 4, 14, 15, 0, 'America/La_Paz'));
        [$cancha] = $this->catalogo();
        $horario = $cancha->horarios()->where('dia_semana', 3)->where('hora_inicio', '16:00:00')->firstOrFail();
        Reserva::factory()->confirmada()->create([
            'cancha_id' => $cancha->id, 'horario_id' => $horario->id,
            'fecha' => '2026-11-04', 'hora_inicio_reserva' => '16:00:00',
            'hora_fin_reserva' => '17:00:00', 'duracion_minutos' => 60,
        ]);

        $franjas = app(PoliticaReservasService::class)->franjasParaConsulta($cancha, '2026-11-04');
        $this->assertSame('15:00:00', $franjas->first()['horario']->hora_inicio);
        $this->assertFalse($franjas->first(fn ($item) => $item['horario']->id === $horario->id)['disponible']);
        $this->get('/disponibilidad?cancha_id='.$cancha->id.'&fecha=2026-11-04')
            ->assertOk()->assertSee('No disponible')->assertSee('15:00');
    }

    public function test_tres_horas_premium_se_confirman_y_bloquean_balon_en_otra_cancha(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 4, 14, 15, 0, 'America/La_Paz'));
        [$cancha, $otra] = $this->catalogo();
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE, 'telefono' => '71234567']);
        $balon = Balon::where('tipo', Balon::TIPO_PREMIUM)->firstOrFail();
        $inicio = $cancha->horarios()->where('dia_semana', 4)->where('hora_inicio', '15:00:00')->firstOrFail();

        $this->actingAs($cliente)->post('/reservas', [
            'cancha_id' => $cancha->id, 'horario_id' => $inicio->id,
            'fecha' => '2026-11-05', 'hora_fin' => '18:00', 'balon_id' => $balon->id,
        ])->assertRedirect(route('reservas.mis'));

        $this->assertDatabaseHas('reservas', [
            'cancha_id' => $cancha->id, 'estado' => Reserva::ESTADO_CONFIRMADA,
            'duracion_minutos' => 180, 'monto_total' => 90,
        ]);
        $this->assertSame(1, $cliente->notifications()->where('data->tipo', 'confirmada')->count());

        $otroInicio = $otra->horarios()->where('dia_semana', 4)->where('hora_inicio', '16:00:00')->firstOrFail();
        $this->actingAs($cliente)->post('/reservas', [
            'cancha_id' => $otra->id, 'horario_id' => $otroInicio->id,
            'fecha' => '2026-11-05', 'hora_fin' => '17:00', 'balon_id' => $balon->id,
        ])->assertStatus(409);
    }

    public function test_hoy_rechaza_una_franja_anterior_a_la_siguiente_hora_completa(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 4, 14, 15, 0, 'America/La_Paz'));
        [$cancha] = $this->catalogo();
        $inicio = $cancha->horarios()->where('dia_semana', 3)->where('hora_inicio', '14:30:00')->firstOrFail();
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE, 'telefono' => '71234567']);

        $this->actingAs($cliente)->post('/reservas', [
            'cancha_id' => $cancha->id, 'horario_id' => $inicio->id,
            'fecha' => '2026-11-04', 'hora_fin' => '15:30',
        ])->assertStatus(422);
        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_no_acepta_huecos_en_bloque_de_dos_horas(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 4, 14, 15, 0, 'America/La_Paz'));
        [$cancha] = $this->catalogo();
        $cancha->horarios()->where('dia_semana', 4)->where('hora_inicio', '16:00:00')->update(['disponible' => false]);
        $inicio = $cancha->horarios()->where('dia_semana', 4)->where('hora_inicio', '15:00:00')->firstOrFail();
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE, 'telefono' => '71234567']);

        $this->actingAs($cliente)->post('/reservas', [
            'cancha_id' => $cancha->id, 'horario_id' => $inicio->id,
            'fecha' => '2026-11-05', 'hora_fin' => '17:00',
        ])->assertStatus(422);
        $this->assertDatabaseCount('reservas', 0);
    }
}
