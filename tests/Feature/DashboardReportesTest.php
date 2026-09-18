<?php

namespace Tests\Feature;

use App\Models\Cancha;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use App\Services\EstadisticasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardReportesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => User::ROL_ADMIN]);
    }

    private function cliente(): User
    {
        return User::factory()->create(['rol' => User::ROL_CLIENTE]);
    }

    private function reservaActiva(Cancha $cancha, Horario $horario, string $fecha, string $estado = Reserva::ESTADO_CONFIRMADA): Reserva
    {
        return Reserva::factory()->create([
            'user_id' => $this->cliente()->id,
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => $fecha,
            'monto_total' => $cancha->precio_por_hora,
            'estado' => $estado,
        ]);
    }

    public function test_un_admin_ve_el_dashboard_con_metricas(): void
    {
        $cancha = Cancha::factory()->create(['precio_por_hora' => 50]);
        $horario = Horario::factory()->create(['cancha_id' => $cancha->id]);
        $this->reservaActiva($cancha, $horario, now()->addDays(2)->toDateString());

        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertSee('Panel operativo');
        $response->assertSee('grafico-dias');
        $response->assertSee('grafico-estados');
    }

    public function test_un_cliente_no_puede_ver_el_dashboard(): void
    {
        $this->actingAs($this->cliente())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_las_metricas_suman_los_datos_correctos(): void
    {
        $cancha = Cancha::factory()->create(['precio_por_hora' => 50]);
        $horario = Horario::factory()->create(['cancha_id' => $cancha->id]);

        $this->reservaActiva($cancha, $horario, now()->addDays(2)->toDateString());          // confirmada
        $this->reservaActiva($cancha, $horario, now()->addDays(3)->toDateString());          // confirmada
        $this->reservaActiva($cancha, $horario, now()->addDays(4)->toDateString(), Reserva::ESTADO_CANCELADA); // cancelada
        Reserva::factory()->cancelada()->create([
            'user_id' => $this->cliente()->id,
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => now()->addDays(5)->toDateString(),
        ]);

        $service = app(EstadisticasService::class);

        // 4 reservas totales (2 confirmadas + 2 canceladas), 2 activas;
        // ingresos = 2 confirmadas × 50
        $this->assertSame(4, $service->metricas()['total_reservas']);
        $this->assertSame(2, $service->reservasActivas());
        $this->assertSame(100.0, $service->ingresosEstimados());
        $this->assertSame(2, $service->metricas()['reservas_confirmadas']);
        $this->assertSame(2, $service->metricas()['reservas_canceladas']);
    }

    public function test_las_canchas_mas_usadas_se_ordenan_por_cantidad(): void
    {
        $a = Cancha::factory()->create(['nombre' => 'Cancha A']);
        $b = Cancha::factory()->create(['nombre' => 'Cancha B']);
        $ha = Horario::factory()->create(['cancha_id' => $a->id]);
        $hb = Horario::factory()->create(['cancha_id' => $b->id]);

        $this->reservaActiva($a, $ha, now()->addDays(2)->toDateString());
        $this->reservaActiva($a, $ha, now()->addDays(3)->toDateString());
        $this->reservaActiva($b, $hb, now()->addDays(2)->toDateString());

        $top = app(EstadisticasService::class)->canchasMasUsadas();

        $this->assertSame('Cancha A', $top->first()['nombre']);
        $this->assertSame(2, $top->first()['total']);
    }

    public function test_un_admin_ve_el_reporte_filtrado_por_rango(): void
    {
        $cancha = Cancha::factory()->create();
        $horario = Horario::factory()->create(['cancha_id' => $cancha->id]);
        $fecha = now()->addDays(2)->toDateString();
        $this->reservaActiva($cancha, $horario, $fecha);

        $desde = now()->addDays(1)->toDateString();
        $hasta = now()->addDays(3)->toDateString();

        $response = $this->actingAs($this->admin())
            ->get("/admin/reportes?desde={$desde}&hasta={$hasta}");

        $response->assertOk();
        $response->assertSee('Reportes operativos');
        $response->assertSee($cancha->nombre);
        $response->assertSee('Exportar CSV');
    }

    public function test_un_cliente_no_puede_ver_los_reportes(): void
    {
        $this->actingAs($this->cliente())
            ->get('/admin/reportes')
            ->assertForbidden();
    }

    public function test_la_exportacion_csv_contiene_los_datos_del_reporte(): void
    {
        $cancha = Cancha::factory()->create(['nombre' => 'Cancha CSV']);
        $horario = Horario::factory()->create(['cancha_id' => $cancha->id]);
        $fecha = now()->addDays(2)->toDateString();
        $this->reservaActiva($cancha, $horario, $fecha);

        $response = $this->actingAs($this->admin())
            ->get('/admin/reportes/exportar');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=utf-8');

        // Captura el contenido emitido por la respuesta de streaming
        ob_start();
        $response->baseResponse->sendContent();
        $contenido = (string) ob_get_clean();

        $this->assertStringContainsString('Cancha CSV', $contenido);
        $this->assertStringContainsString('Cliente', $contenido);
    }

    public function test_el_reporte_invierte_el_rango_si_hasta_es_anterior_a_desde(): void
    {
        $cancha = Cancha::factory()->create();
        $horario = Horario::factory()->create(['cancha_id' => $cancha->id]);
        $fecha = now()->addDays(2)->toDateString();
        $this->reservaActiva($cancha, $horario, $fecha);

        $desde = now()->addDays(3)->toDateString();
        $hasta = now()->addDays(1)->toDateString();

        $response = $this->actingAs($this->admin())
            ->get("/admin/reportes?desde={$desde}&hasta={$hasta}");

        $response->assertOk();
        $response->assertSee($cancha->nombre);
    }

    public function test_la_pagina_de_inicio_muestra_canchas_destacadas(): void
    {
        $cancha = Cancha::factory()->create(['nombre' => 'Cancha Destacada', 'estado' => Cancha::ESTADO_DISPONIBLE]);
        Cancha::factory()->create(['estado' => Cancha::ESTADO_MANTENIMIENTO]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Canchas destacadas');
        $response->assertSee('Cancha Destacada');
        $response->assertSee('Reserva tu cancha');
        $response->assertSee('Reservar ahora');
        $response->assertDontSee('Canchas en mantenimiento');
    }
}
