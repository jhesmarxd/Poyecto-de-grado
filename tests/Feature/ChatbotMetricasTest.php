<?php

namespace Tests\Feature;

use App\Models\Conversacion;
use App\Models\MensajeChatbot;
use App\Models\User;
use App\Services\Chatbot\DetectorIntencion;
use App\Services\Chatbot\ReservaConversacionalService;
use App\Services\ChatbotMetricasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ChatbotMetricasTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_calcula_metricas_verificables_del_periodo(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 15, 12, 0, 0, 'America/La_Paz'));
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);
        $registrada = Conversacion::factory()->create([
            'user_id' => $cliente->id,
            'contexto' => [
                ReservaConversacionalService::CONTEXTO_RESERVAS_CREADAS => [[
                    'reserva_id' => 81,
                    'created_at' => now()->toIso8601String(),
                ]],
            ],
        ]);
        $visitante = Conversacion::factory()->create();
        $antigua = Conversacion::factory()->create(['user_id' => $cliente->id]);

        $this->mensaje($registrada, DetectorIntencion::CONSULTAR_PRECIOS);
        $this->mensaje($registrada, DetectorIntencion::HACER_RESERVA);
        $this->mensaje($visitante, DetectorIntencion::SOLICITUD_BLOQUEADA);
        $this->mensaje($visitante, DetectorIntencion::NO_DETECTADA);
        $this->mensaje($antigua, DetectorIntencion::CONSULTAR_HORARIOS, now()->subDays(40));

        $metricas = app(ChatbotMetricasService::class)->panel('30');

        $this->assertSame(2, $metricas['total_conversaciones']);
        $this->assertSame(4, $metricas['total_mensajes']);
        $this->assertSame(1, $metricas['usuarios_identificados']);
        $this->assertSame(1, $metricas['conversaciones_visitantes']);
        $this->assertSame(1, $metricas['reservas_creadas']);
        $this->assertSame(50.0, $metricas['tasa_conversion']);
        $this->assertSame(1, $metricas['consultas_bloqueadas']);
        $this->assertSame(25.0, $metricas['tasa_bloqueo']);
        $this->assertSame(1, $metricas['consultas_no_resueltas']);
        $this->assertSame(25.0, $metricas['tasa_no_resueltas']);
        $this->assertSame(2.0, $metricas['mensajes_por_conversacion']);
        $this->assertCount(30, $metricas['actividad']);
    }

    public function test_solo_el_administrador_puede_ver_el_panel_de_metricas(): void
    {
        $admin = User::factory()->create(['rol' => User::ROL_ADMIN]);
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);

        $this->actingAs($admin)
            ->get(route('admin.chatbot.metricas', ['periodo' => '7']))
            ->assertOk()
            ->assertSee('Métricas del chatbot')
            ->assertSee('Conversaciones activas')
            ->assertSee('Reservas generadas')
            ->assertSee('últimos 7 días');

        $this->actingAs($cliente)
            ->get(route('admin.chatbot.metricas'))
            ->assertForbidden();
    }

    private function mensaje(
        Conversacion $conversacion,
        string $intencion,
        ?Carbon $fecha = null
    ): MensajeChatbot {
        $mensaje = $conversacion->mensajes()->create([
            'emisor' => MensajeChatbot::EMISOR_USUARIO,
            'contenido' => 'Contenido de prueba',
            'intencion' => $intencion,
        ]);

        if ($fecha) {
            $mensaje->forceFill([
                'created_at' => $fecha,
                'updated_at' => $fecha,
            ])->save();
        }

        return $mensaje;
    }
}
