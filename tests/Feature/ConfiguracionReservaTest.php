<?php

namespace Tests\Feature;

use App\Models\CambioConfiguracionReserva;
use App\Models\Cancha;
use App\Models\ConfiguracionReserva;
use App\Models\ExcepcionReserva;
use App\Models\Horario;
use App\Models\HorarioSemanal;
use App\Models\Reserva;
use App\Models\ReservaAfectadaConfiguracion;
use App\Models\User;
use App\Services\PoliticaReservasService;
use App\Services\Reservas\ResultadoDisponibilidad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ConfiguracionReservaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => User::ROL_ADMIN]);
    }

    private function cliente(): User
    {
        return User::factory()->create(['rol' => User::ROL_CLIENTE, 'telefono' => '71234567']);
    }

    private function canchaConFranja(string $fecha, string $nombre = 'Cancha 1'): array
    {
        $cancha = Cancha::factory()->create(['nombre' => $nombre]);
        $horario = Horario::factory()->create([
            'cancha_id' => $cancha->id,
            'dia_semana' => Carbon::parse($fecha)->dayOfWeekIso,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
        ]);

        return [$cancha, $horario];
    }

    private function fechaAbierta(int $diasMinimos = 1): string
    {
        $fecha = now()->startOfDay()->addDays($diasMinimos);
        while ($fecha->dayOfWeekIso === 7) {
            $fecha->addDay();
        }

        return $fecha->toDateString();
    }

    public function test_solo_el_administrador_accede_a_la_configuracion_central(): void
    {
        $this->actingAs($this->admin())->get(route('admin.configuracion-reservas.index'))->assertOk();
        $this->actingAs($this->cliente())->get(route('admin.configuracion-reservas.index'))->assertForbidden();
    }

    public function test_la_anticipacion_maxima_se_aplica_desde_una_fuente_central(): void
    {
        ConfiguracionReserva::query()->firstOrFail()->update(['anticipacion_maxima_dias' => 7]);
        $politica = app(PoliticaReservasService::class);

        $this->assertNull($politica->validarFecha(now()->addDays(7)->toDateString()));
        $this->assertSame('fuera_de_rango', $politica->validarFecha(now()->addDays(8)->toDateString())?->estado);
    }

    public function test_el_horario_global_cierra_el_dia_para_todas_las_canchas(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha] = $this->canchaConFranja($fecha);
        HorarioSemanal::query()->where('dia_semana', Carbon::parse($fecha)->dayOfWeekIso)->update(['abierto' => false]);

        $this->assertTrue(app(PoliticaReservasService::class)->horariosLibres($cancha, $fecha)->isEmpty());
    }

    public function test_un_bloqueo_de_una_cancha_no_afecta_a_la_otra(): void
    {
        $fecha = $this->fechaAbierta(3);
        [$cancha1] = $this->canchaConFranja($fecha, 'Cancha 1');
        [$cancha2] = $this->canchaConFranja($fecha, 'Cancha 2');
        ExcepcionReserva::create([
            'cancha_id' => $cancha1->id,
            'fecha' => $fecha,
            'tipo' => ExcepcionReserva::BLOQUEO_PARCIAL,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
            'motivo' => 'Mantenimiento',
            'estado' => ExcepcionReserva::ESTADO_APLICADA,
        ]);

        $politica = app(PoliticaReservasService::class);
        $this->assertTrue($politica->horariosLibres($cancha1, $fecha)->isEmpty());
        $this->assertCount(1, $politica->horariosLibres($cancha2, $fecha));
    }

    public function test_el_resultado_estructurado_distingue_una_sola_cancha_disponible(): void
    {
        $fecha = $this->fechaAbierta(3);
        [$cancha1, $horario1] = $this->canchaConFranja($fecha, 'Cancha 1');
        [$cancha2] = $this->canchaConFranja($fecha, 'Cancha 2');
        Reserva::create([
            'user_id' => $this->cliente()->id,
            'cancha_id' => $cancha1->id,
            'horario_id' => $horario1->id,
            'fecha' => $fecha,
            'hora_inicio_reserva' => '18:00:00',
            'hora_fin_reserva' => '19:00:00',
            'duracion_minutos' => 60,
            'monto_total' => $cancha1->precio_por_hora,
            'tipo_balon' => Reserva::BALON_PROPIO,
            'estado' => Reserva::ESTADO_CONFIRMADA,
        ]);

        $resultado = app(PoliticaReservasService::class)
            ->consultarRango($fecha, '18:00', '19:00');

        $this->assertSame(ResultadoDisponibilidad::UNA_CANCHA, $resultado->estado);
        $this->assertTrue($resultado->disponible());
        $this->assertSame([$cancha2->id], $resultado->canchas->pluck('id')->all());
    }

    public function test_una_excepcion_con_conflictos_exige_revision_y_al_aplicarse_notifica_y_marca(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConFranja($fecha);
        $reserva = Reserva::factory()->confirmada()->create([
            'user_id' => $this->cliente()->id,
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
        ]);

        $respuesta = $this->actingAs($this->admin())->post(route('admin.configuracion-reservas.exceptions.preview'), [
            'cancha_id' => $cancha->id,
            'fecha' => $fecha,
            'tipo' => ExcepcionReserva::CIERRE_COMPLETO,
            'motivo' => 'Viaje',
            'mensaje_cliente' => 'Tu reserva de [hora_inicio] a [hora_fin] será afectada.',
        ]);

        $cambio = CambioConfiguracionReserva::firstOrFail();
        $respuesta->assertRedirect(route('admin.configuracion-reservas.review', $cambio));
        $this->assertSame(CambioConfiguracionReserva::ESTADO_PENDIENTE, $cambio->estado);
        $this->assertDatabaseHas('reservas_afectadas_configuracion', ['reserva_id' => $reserva->id]);

        $this->post(route('admin.configuracion-reservas.apply', $cambio))->assertRedirect();

        $this->assertSame(Reserva::ESTADO_AFECTADA, $reserva->fresh()->estado);
        $this->assertSame(ExcepcionReserva::ESTADO_APLICADA, $cambio->excepciones()->firstOrFail()->estado);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $reserva->user_id,
            'notifiable_type' => User::class,
        ]);

        $this->post(route('admin.configuracion-reservas.apply', $cambio));
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_una_reserva_afectada_puede_cancelarse_sin_eliminar_su_historial(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        [$cancha, $horario] = $this->canchaConFranja($fecha);
        $reserva = Reserva::factory()->create([
            'user_id' => $this->cliente()->id,
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
            'estado' => Reserva::ESTADO_AFECTADA,
        ]);
        $cambio = CambioConfiguracionReserva::create([
            'administrador_id' => $this->admin()->id,
            'tipo' => 'excepcion',
            'valor_nuevo' => ['fecha' => $fecha],
            'estado' => CambioConfiguracionReserva::ESTADO_APLICADO,
        ]);
        $afectada = ReservaAfectadaConfiguracion::create([
            'cambio_configuracion_id' => $cambio->id,
            'reserva_id' => $reserva->id,
            'estado_anterior' => Reserva::ESTADO_CONFIRMADA,
            'estado_nuevo' => Reserva::ESTADO_AFECTADA,
        ]);

        $this->actingAs($cambio->administrador)
            ->post(route('admin.configuracion-reservas.affected.cancel', $afectada))
            ->assertRedirect();

        $this->assertDatabaseHas('reservas', ['id' => $reserva->id, 'estado' => Reserva::ESTADO_CANCELADA_COMPLEJO]);
        $this->assertNotNull($afectada->fresh()->resuelto_at);
    }

    public function test_un_fallo_de_notificacion_revierte_la_aplicacion_completa(): void
    {
        $fecha = $this->fechaAbierta(3);
        [$cancha, $horario] = $this->canchaConFranja($fecha);
        $administrador = $this->admin();
        $reserva = Reserva::factory()->confirmada()->create([
            'user_id' => $administrador->id,
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
        ]);
        $this->actingAs($administrador)->post(route('admin.configuracion-reservas.exceptions.preview'), [
            'cancha_id' => $cancha->id,
            'fecha' => $fecha,
            'tipo' => ExcepcionReserva::CIERRE_COMPLETO,
            'motivo' => 'Prueba de reversión',
        ])->assertRedirect();

        $cambio = CambioConfiguracionReserva::query()->latest('id')->firstOrFail();
        $this->post(route('admin.configuracion-reservas.apply', $cambio))->assertStatus(422);

        $this->assertSame(CambioConfiguracionReserva::ESTADO_PENDIENTE, $cambio->fresh()->estado);
        $this->assertSame(ExcepcionReserva::ESTADO_BORRADOR, $cambio->excepciones()->firstOrFail()->estado);
        $this->assertSame(Reserva::ESTADO_CONFIRMADA, $reserva->fresh()->estado);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_la_configuracion_global_materializa_franjas_de_treinta_minutos(): void
    {
        $cancha = Cancha::factory()->create();
        $horarios = [];
        foreach (range(1, 7) as $dia) {
            $horarios[$dia] = [
                'abierto' => $dia === 1 ? '1' : '0',
                'hora_apertura' => '18:00',
                'hora_cierre' => '20:00',
            ];
        }

        $this->actingAs($this->admin())->post(route('admin.configuracion-reservas.preview'), [
            'anticipacion_maxima_dias' => 10,
            'permitir_hoy' => '1',
            'permitir_futuras' => '1',
            'duraciones_permitidas' => [60, 90, 120],
            'horarios' => $horarios,
            'motivo' => 'Horario de prueba',
        ])->assertRedirect(route('admin.configuracion-reservas.index'));

        $this->assertSame(10, ConfiguracionReserva::query()->firstOrFail()->anticipacion_maxima_dias);
        $this->assertCount(4, $cancha->horarios()->where('dia_semana', 1)->where('disponible', true)->get());
        $this->assertTrue($cancha->horarios()->where('dia_semana', 2)->where('disponible', true)->doesntExist());
    }

    public function test_una_reserva_afectada_puede_reprogramarse_con_vinculo_historico(): void
    {
        $fechaOriginal = $this->fechaAbierta(2);
        $fechaNuevaCarbon = Carbon::parse($fechaOriginal)->addDay();
        if ($fechaNuevaCarbon->dayOfWeekIso === 7) {
            $fechaNuevaCarbon->addDay();
        }
        $fechaNueva = $fechaNuevaCarbon->toDateString();
        [$cancha, $horarioOriginal] = $this->canchaConFranja($fechaOriginal);
        $horarioNuevo = Horario::factory()->create([
            'cancha_id' => $cancha->id,
            'dia_semana' => Carbon::parse($fechaNueva)->dayOfWeekIso,
            'hora_inicio' => '19:00:00',
            'hora_fin' => '20:00:00',
        ]);
        $original = Reserva::factory()->create([
            'user_id' => $this->cliente()->id,
            'cancha_id' => $cancha->id,
            'horario_id' => $horarioOriginal->id,
            'fecha' => $fechaOriginal,
            'estado' => Reserva::ESTADO_AFECTADA,
        ]);
        $cambio = CambioConfiguracionReserva::create([
            'administrador_id' => $this->admin()->id,
            'tipo' => 'excepcion',
            'valor_nuevo' => ['fecha' => $fechaOriginal],
            'estado' => CambioConfiguracionReserva::ESTADO_APLICADO,
        ]);
        $afectada = ReservaAfectadaConfiguracion::create([
            'cambio_configuracion_id' => $cambio->id,
            'reserva_id' => $original->id,
            'estado_anterior' => Reserva::ESTADO_CONFIRMADA,
            'estado_nuevo' => Reserva::ESTADO_AFECTADA,
        ]);

        $this->actingAs($cambio->administrador)->post(
            route('admin.configuracion-reservas.affected.reschedule', $afectada),
            ['cancha_id' => $cancha->id, 'horario_id' => $horarioNuevo->id, 'fecha' => $fechaNueva, 'hora_fin' => '20:00']
        )->assertRedirect();

        $this->assertSame(Reserva::ESTADO_REPROGRAMADA, $original->fresh()->estado);
        $nueva = Reserva::query()->where('reprogramada_desde_id', $original->id)->firstOrFail();
        $this->assertSame($fechaNueva, $nueva->fecha->toDateString());
        $this->assertSame(Reserva::ESTADO_CONFIRMADA, $nueva->estado);
    }
}
