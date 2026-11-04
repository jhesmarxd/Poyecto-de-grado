<?php

namespace Tests\Unit;

use App\Models\Balon;
use App\Models\Cancha;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use App\Services\ReservaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ReservaServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReservaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReservaService;
    }

    public function test_es_fecha_valida_con_formato_correcto(): void
    {
        $this->assertTrue($this->service->esFechaValida('2026-09-15'));
        $this->assertTrue($this->service->esFechaValida('2026-12-31'));
    }

    public function test_es_fecha_valida_con_formato_incorrecto(): void
    {
        $this->assertFalse($this->service->esFechaValida('15/09/2026'));
        $this->assertFalse($this->service->esFechaValida('2026-9-15'));
        $this->assertFalse($this->service->esFechaValida(null));
        $this->assertFalse($this->service->esFechaValida(''));
    }

    public function test_balones_ocupados_devuelve_balones_reservados(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        $cancha = Cancha::factory()->create();
        $diaSemana = Carbon::parse($fecha)->dayOfWeekIso;
        $horario = Horario::factory()->create([
            'cancha_id' => $cancha->id,
            'dia_semana' => $diaSemana,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
        ]);

        $balon = Balon::create([
            'tipo' => Balon::TIPO_ECONOMICO,
            'marca' => 'Test',
            'nombre' => '1',
            'precio_por_hora' => 25.00,
        ]);

        Reserva::factory()->confirmada()->create([
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
            'balon_id' => $balon->id,
        ]);

        $ocupados = $this->service->balonesOcupados($fecha, $horario);

        $this->assertContains($balon->id, $ocupados);
    }

    public function test_balones_ocupados_no_incluye_balones_de_otra_hora(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        $cancha = Cancha::factory()->create();
        $diaSemana = Carbon::parse($fecha)->dayOfWeekIso;

        $horario1 = Horario::factory()->create([
            'cancha_id' => $cancha->id,
            'dia_semana' => $diaSemana,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
        ]);

        $horario2 = Horario::factory()->create([
            'cancha_id' => $cancha->id,
            'dia_semana' => $diaSemana,
            'hora_inicio' => '19:00:00',
            'hora_fin' => '20:00:00',
        ]);

        $balon = Balon::create([
            'tipo' => Balon::TIPO_ECONOMICO,
            'marca' => 'Test',
            'nombre' => '1',
            'precio_por_hora' => 25.00,
        ]);

        Reserva::factory()->confirmada()->create([
            'cancha_id' => $cancha->id,
            'horario_id' => $horario1->id,
            'fecha' => $fecha,
            'balon_id' => $balon->id,
        ]);

        $ocupados = $this->service->balonesOcupados($fecha, $horario2);

        $this->assertNotContains($balon->id, $ocupados);
    }

    public function test_horario_ocupado_returns_true_when_reserved(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        $cancha = Cancha::factory()->create();
        $diaSemana = Carbon::parse($fecha)->dayOfWeekIso;
        $horario = Horario::factory()->create([
            'cancha_id' => $cancha->id,
            'dia_semana' => $diaSemana,
        ]);

        Reserva::factory()->create([
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
        ]);

        $this->assertTrue($this->service->horarioOcupado($cancha->id, $fecha, $horario->id));
    }

    public function test_horario_ocupado_returns_false_when_available(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        $cancha = Cancha::factory()->create();
        $diaSemana = Carbon::parse($fecha)->dayOfWeekIso;
        $horario = Horario::factory()->create([
            'cancha_id' => $cancha->id,
            'dia_semana' => $diaSemana,
        ]);

        $this->assertFalse($this->service->horarioOcupado($cancha->id, $fecha, $horario->id));
    }

    public function test_determinar_tipo_balon_con_balón_premium(): void
    {
        $balon = Balon::create([
            'tipo' => Balon::TIPO_PREMIUM,
            'marca' => 'Mikasa',
            'nombre' => 'V200W',
            'precio_por_hora' => 30.00,
        ]);

        $this->assertEquals(Reserva::BALON_PREMIUM, $this->service->determinarTipoBalon($balon));
    }

    public function test_determinar_tipo_balon_con_balón_económico(): void
    {
        $balon = Balon::create([
            'tipo' => Balon::TIPO_ECONOMICO,
            'marca' => 'Test',
            'nombre' => '1',
            'precio_por_hora' => 25.00,
        ]);

        $this->assertEquals(Reserva::BALON_PRESTADO, $this->service->determinarTipoBalon($balon));
    }

    public function test_determinar_tipo_balon_sin_balón(): void
    {
        $this->assertEquals(Reserva::BALON_PROPIO, $this->service->determinarTipoBalon(null));
    }

    public function test_calcular_monto_con_balón(): void
    {
        $balon = Balon::create([
            'tipo' => Balon::TIPO_ECONOMICO,
            'marca' => 'Test',
            'nombre' => '1',
            'precio_por_hora' => 25.00,
        ]);

        $monto = $this->service->calcularMonto(20.00, $balon->id);

        $this->assertEquals(25.00, $monto);
    }

    public function test_calcular_monto_sin_balón(): void
    {
        $monto = $this->service->calcularMonto(20.00, null);

        $this->assertEquals(20.00, $monto);
    }

    public function test_cambiar_estado_returns_true_when_valid_transition(): void
    {
        $reserva = Reserva::factory()->create([
            'estado' => Reserva::ESTADO_PENDIENTE,
        ]);

        $this->service->cambiarEstado($reserva, Reserva::ESTADO_CONFIRMADA);

        $this->assertDatabaseHas('reservas', [
            'id' => $reserva->id,
            'estado' => Reserva::ESTADO_CONFIRMADA,
        ]);
    }

    public function test_cambiar_estado_throws_when_already_cancelled(): void
    {
        $reserva = Reserva::factory()->cancelada()->create();

        $this->expectException(\Exception::class);
        $this->service->cambiarEstado($reserva, Reserva::ESTADO_CONFIRMADA);
    }

    public function test_la_restriccion_de_bd_se_traduce_en_conflicto_de_negocio(): void
    {
        $fecha = now()->addDays(3)->toDateString();
        $cancha = Cancha::factory()->create();
        $horario = Horario::factory()->create([
            'cancha_id' => $cancha->id,
            'dia_semana' => Carbon::parse($fecha)->dayOfWeekIso,
        ]);
        $usuario = User::factory()->create(['telefono' => '71234567']);

        Reserva::factory()->create([
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
        ]);

        $servicioSinPreconsulta = new class extends ReservaService
        {
            public function horarioOcupado(int $canchaId, string $fecha, int $horarioId): bool
            {
                return false;
            }
        };

        try {
            $servicioSinPreconsulta->crearReserva($usuario, [
                'cancha_id' => $cancha->id,
                'horario_id' => $horario->id,
                'fecha' => $fecha,
            ]);

            $this->fail('La base de datos debía rechazar la reserva duplicada.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }

        $this->assertDatabaseCount('reservas', 1);
    }
}
