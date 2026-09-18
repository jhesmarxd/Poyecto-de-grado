<?php

namespace Tests\Feature;

use App\Models\Cancha;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use App\Services\ReservaNotificationService;
use App\Services\ReservaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReservaNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_notifica_confirmacion_y_cancelacion_sin_duplicarlas(): void
    {
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);
        $reserva = $this->reserva($cliente, '18:00:00', '19:00:00', Reserva::ESTADO_PENDIENTE);
        $servicio = app(ReservaService::class);

        $servicio->cambiarEstado($reserva, Reserva::ESTADO_CONFIRMADA);
        $servicio->cambiarEstado($reserva->fresh(), Reserva::ESTADO_CONFIRMADA);

        $this->assertCount(1, $cliente->fresh()->notifications);
        $this->assertSame(
            ReservaNotificationService::TIPO_CONFIRMADA,
            $cliente->fresh()->notifications->first()->data['tipo']
        );

        $servicio->cancelarReserva($reserva->fresh());

        $this->assertCount(2, $cliente->fresh()->notifications);
        $this->assertTrue(
            $cliente->fresh()->notifications->contains(
                fn ($notificacion) => $notificacion->data['tipo'] === ReservaNotificationService::TIPO_CANCELADA
            )
        );
    }

    public function test_el_comando_genera_cada_recordatorio_una_sola_vez(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 16, 0, 0, 'America/La_Paz'));
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);

        $this->reserva($cliente, '18:00:00', '19:00:00');
        $this->reserva($cliente, '16:30:00', '17:30:00');
        $this->reserva($cliente, '15:30:00', '16:30:00');
        $this->reserva($cliente, '14:00:00', '15:00:00');

        $this->artisan('reservas:enviar-recordatorios')
            ->expectsOutput('Notificaciones generadas: 4')
            ->assertSuccessful();
        $this->artisan('reservas:enviar-recordatorios')
            ->expectsOutput('Notificaciones generadas: 0')
            ->assertSuccessful();

        $notificaciones = $cliente->fresh()->notifications;
        $tipos = $notificaciones->pluck('data.tipo');

        $this->assertCount(4, $notificaciones);
        $this->assertTrue($tipos->contains(ReservaNotificationService::TIPO_RECORDATORIO_2H));
        $this->assertTrue($tipos->contains(ReservaNotificationService::TIPO_RECORDATORIO_30M));
        $this->assertTrue($tipos->contains(ReservaNotificationService::TIPO_INICIADA));
        $this->assertTrue($tipos->contains(ReservaNotificationService::TIPO_FINALIZADA));
    }

    public function test_el_cliente_solo_ve_y_marca_sus_propias_notificaciones(): void
    {
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);
        $otroCliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);
        $reserva = $this->reserva($cliente, '18:00:00', '19:00:00');
        $otraReserva = $this->reserva($otroCliente, '19:00:00', '20:00:00');
        $servicio = app(ReservaNotificationService::class);
        $servicio->enviar($reserva, ReservaNotificationService::TIPO_CONFIRMADA);
        $servicio->enviar($otraReserva, ReservaNotificationService::TIPO_CONFIRMADA);

        $notificacion = $cliente->fresh()->notifications->first();
        $ajena = $otroCliente->fresh()->notifications->first();

        $this->actingAs($cliente)
            ->get(route('notificaciones.index'))
            ->assertOk()
            ->assertSee($reserva->cancha->nombre)
            ->assertDontSee($otraReserva->cancha->nombre);

        $this->actingAs($cliente)
            ->post(route('notificaciones.leer', $notificacion))
            ->assertRedirect(route('reservas.mis'));

        $this->assertNotNull($notificacion->fresh()->read_at);

        $this->actingAs($cliente)
            ->post(route('notificaciones.leer', $ajena))
            ->assertForbidden();
    }

    public function test_roles_operativos_no_acceden_a_notificaciones_de_clientes(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);

        $this->actingAs($admin)
            ->get(route('notificaciones.index'))
            ->assertForbidden();
    }

    private function reserva(
        User $cliente,
        string $inicio,
        string $fin,
        string $estado = Reserva::ESTADO_CONFIRMADA
    ): Reserva {
        $fecha = Carbon::now('America/La_Paz')->toDateString();
        $cancha = Cancha::factory()->create();
        $horario = Horario::factory()->create([
            'cancha_id' => $cancha->id,
            'dia_semana' => Carbon::parse($fecha)->dayOfWeekIso,
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
