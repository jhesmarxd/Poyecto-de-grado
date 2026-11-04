<?php

namespace Tests\Feature;

use App\Models\Cancha;
use App\Models\ChatbotEjecucion;
use App\Models\Conversacion;
use App\Models\MensajeChatbot;
use App\Models\User;
use App\Services\Chatbot\ConversationLifecycleService;
use App\Services\Chatbot\DetectorIntencion;
use App\Services\Chatbot\HistorialConversacionalBuilder;
use App\Services\Chatbot\ReservaConversacionalService;
use App\Services\Chatbot\ResultadoProveedorNlp;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotConversationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('chatbot.provider', null);
        config()->set('chatbot.gemini_api_key', null);
        config()->set('chatbot.conversation.ttl_minutes', 30);
        config()->set('chatbot.history.max_user_messages', 6);
        config()->set('chatbot.history.max_bot_messages', 6);
        Http::preventStrayRequests();

        $this->cliente = User::factory()->create([
            'rol' => User::ROL_CLIENTE,
            'estado' => true,
            'telefono' => '71234567',
        ]);
        $this->actingAs($this->cliente);
    }

    public function test_usuario_sin_conversacion_crea_una_activa(): void
    {
        $response = $this->withSession([])
            ->postJson(route('chatbot.mensaje'), ['mensaje' => 'hola'])
            ->assertOk();

        $conversacion = Conversacion::sole();
        $this->assertSame(Conversacion::STATUS_ACTIVE, $conversacion->status);
        $this->assertSame($this->cliente->id, $conversacion->user_id);
        $this->assertNotNull($conversacion->last_activity_at);
        $this->assertNotNull($conversacion->context_expires_at);
        $response->assertSessionHas('conversacion_id', $conversacion->id);
    }

    public function test_conversacion_activa_reciente_se_reutiliza(): void
    {
        $conversacion = Conversacion::factory()->create([
            'user_id' => $this->cliente->id,
            'last_activity_at' => now()->subMinutes(5),
            'contexto' => ['cancha_id' => 81],
        ]);

        $this->withSession([])
            ->postJson(route('chatbot.mensaje'), ['mensaje' => 'hola'])
            ->assertOk();

        $this->assertDatabaseCount('conversaciones', 1);
        $this->assertSame(2, $conversacion->mensajes()->count());
        $this->assertTrue($conversacion->fresh()->last_activity_at->greaterThan(now()->subMinute()));
    }

    public function test_usuarios_distintos_obtienen_ciclos_de_vida_independientes(): void
    {
        $otro = User::factory()->create([
            'rol' => User::ROL_CLIENTE,
            'estado' => true,
        ]);
        $lifecycle = app(ConversationLifecycleService::class);

        $primera = $lifecycle->obtenerOCrearActiva($this->cliente);
        $segunda = $lifecycle->obtenerOCrearActiva($otro);

        $this->assertNotSame($primera->id, $segunda->id);
        $this->assertSame($this->cliente->id, $primera->user_id);
        $this->assertSame($otro->id, $segunda->user_id);
        $this->assertDatabaseCount('conversaciones', 2);
    }

    public function test_el_ciclo_de_vida_consolida_duplicados_activos_del_mismo_usuario(): void
    {
        $anterior = Conversacion::factory()->create([
            'user_id' => $this->cliente->id,
            'last_activity_at' => now()->subMinutes(2),
        ]);
        $reciente = Conversacion::factory()->create([
            'user_id' => $this->cliente->id,
            'last_activity_at' => now()->subMinute(),
        ]);

        $obtenida = app(ConversationLifecycleService::class)
            ->obtenerOCrearActiva($this->cliente);

        $this->assertSame($reciente->id, $obtenida->id);
        $this->assertSame(Conversacion::STATUS_CLOSED, $anterior->fresh()->status);
        $this->assertSame(1, Conversacion::query()
            ->where('user_id', $this->cliente->id)
            ->where('status', Conversacion::STATUS_ACTIVE)
            ->count());
    }

    public function test_conversacion_mayor_al_ttl_expira_y_crea_otra(): void
    {
        $anterior = Conversacion::factory()->create([
            'user_id' => $this->cliente->id,
            'last_activity_at' => now()->subMinutes(31),
            'context_expires_at' => now()->subMinute(),
            'contexto' => [
                'cancha_id' => 45,
                ReservaConversacionalService::CONTEXTO_BORRADOR => [
                    'estado' => 'recopilando',
                    'expires_at' => now()->addMinutes(5)->toIso8601String(),
                ],
            ],
        ]);

        $response = $this->withSession(['conversacion_id' => $anterior->id])
            ->postJson(route('chatbot.mensaje'), ['mensaje' => 'hola'])
            ->assertOk();

        $anterior->refresh();
        $nueva = Conversacion::query()->whereKeyNot($anterior->id)->sole();
        $this->assertSame(Conversacion::STATUS_EXPIRED, $anterior->status);
        $this->assertArrayNotHasKey(
            ReservaConversacionalService::CONTEXTO_BORRADOR,
            $anterior->contexto
        );
        $this->assertSame(Conversacion::STATUS_ACTIVE, $nueva->status);
        $this->assertSame([], $nueva->contexto);
        $response->assertSessionHas('conversacion_id', $nueva->id);
    }

    public function test_conversacion_nueva_no_hereda_contexto_anterior(): void
    {
        $anterior = Conversacion::factory()->create([
            'user_id' => $this->cliente->id,
            'last_activity_at' => now()->subHour(),
            'contexto' => [
                'cancha_id' => 9,
                'fecha' => '2026-09-30',
                'hora' => '19:00:00',
                'hora_fin' => '20:00:00',
                'balon' => 'premium',
                'intencion_pendiente' => DetectorIntencion::CONSULTAR_DISPONIBILIDAD,
            ],
        ]);

        $nueva = app(ConversationLifecycleService::class)
            ->obtenerOCrearActiva($this->cliente, $anterior->id);

        $this->assertNotSame($anterior->id, $nueva->id);
        $this->assertSame([], $nueva->contexto);
        $this->assertSame(9, $anterior->fresh()->contexto['cancha_id']);
    }

    public function test_usuario_nunca_reutiliza_la_conversacion_de_otro_usuario(): void
    {
        $otro = User::factory()->create(['rol' => User::ROL_CLIENTE, 'estado' => true]);
        $privada = Conversacion::factory()->create([
            'user_id' => $otro->id,
            'contexto' => ['cancha_id' => 77],
        ]);

        $this->withSession(['conversacion_id' => $privada->id])
            ->postJson(route('chatbot.mensaje'), ['mensaje' => 'hola'])
            ->assertOk();

        $propia = Conversacion::query()->where('user_id', $this->cliente->id)->sole();
        $this->assertNotSame($privada->id, $propia->id);
        $this->assertSame(['cancha_id' => 77], $privada->fresh()->contexto);
        $this->assertSame([], $propia->contexto);
    }

    public function test_borrador_menor_a_diez_minutos_sigue_valido(): void
    {
        $conversacion = $this->conBorrador(now()->addMinutes(5)->toIso8601String());

        $limpio = app(ConversationLifecycleService::class)
            ->limpiarContextoTransitorioVencido($conversacion);

        $this->assertFalse($limpio);
        $this->assertIsArray(
            $conversacion->fresh()->contextoDe(ReservaConversacionalService::CONTEXTO_BORRADOR)
        );
    }

    public function test_borrador_mayor_a_diez_minutos_se_limpia(): void
    {
        $conversacion = $this->conBorrador(now()->subMinute()->toIso8601String());

        $limpio = app(ConversationLifecycleService::class)
            ->limpiarContextoTransitorioVencido($conversacion);

        $contexto = $conversacion->fresh()->contexto;
        $this->assertTrue($limpio);
        $this->assertArrayNotHasKey(ReservaConversacionalService::CONTEXTO_BORRADOR, $contexto);
        $this->assertArrayNotHasKey('intencion_pendiente', $contexto);
        $this->assertSame(12, $contexto['cancha_id']);
    }

    public function test_reserva_confirmada_limpia_el_borrador(): void
    {
        $fecha = now()->addDay();
        $cancha = Cancha::factory()->create();
        $horario = $cancha->horarios()->create([
            'dia_semana' => $fecha->dayOfWeekIso,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
            'disponible' => true,
        ]);
        $conversacion = Conversacion::factory()->create([
            'user_id' => $this->cliente->id,
            'contexto' => [
                ReservaConversacionalService::CONTEXTO_BORRADOR => [
                    'estado' => 'confirmacion',
                    'cancha_id' => $cancha->id,
                    'fecha' => $fecha->toDateString(),
                    'hora' => $horario->hora_inicio,
                    'hora_fin' => $horario->hora_fin,
                    'horario_id' => $horario->id,
                    'balon_elegido' => true,
                    'balon_id' => null,
                    'expires_at' => now()->addMinutes(5)->toIso8601String(),
                ],
                'intencion_pendiente' => DetectorIntencion::HACER_RESERVA,
            ],
        ]);

        app(ChatbotService::class)->responder(
            'sí, confirmar',
            $conversacion,
            $this->cliente,
        );

        $this->assertNull(
            $conversacion->fresh()->contextoDe(ReservaConversacionalService::CONTEXTO_BORRADOR)
        );
        $this->assertDatabaseHas('reservas', [
            'user_id' => $this->cliente->id,
            'cancha_id' => $cancha->id,
        ]);
    }

    public function test_borrador_descartado_se_limpia(): void
    {
        $conversacion = Conversacion::factory()->create([
            'user_id' => $this->cliente->id,
            'contexto' => [
                ReservaConversacionalService::CONTEXTO_BORRADOR => [
                    'estado' => 'confirmacion',
                    'expires_at' => now()->addMinutes(5)->toIso8601String(),
                ],
                'intencion_pendiente' => DetectorIntencion::HACER_RESERVA,
            ],
        ]);

        app(ChatbotService::class)->responder('no', $conversacion, $this->cliente);

        $contexto = $conversacion->fresh()->contexto;
        $this->assertArrayNotHasKey(ReservaConversacionalService::CONTEXTO_BORRADOR, $contexto);
        $this->assertArrayNotHasKey('intencion_pendiente', $contexto);
    }

    public function test_nueva_conversacion_explicita_cierra_la_anterior(): void
    {
        $anterior = $this->conBorrador(now()->addMinutes(5)->toIso8601String());

        $response = $this->withSession(['conversacion_id' => $anterior->id])
            ->postJson(route('chatbot.nueva-conversacion'))
            ->assertOk()
            ->assertJsonPath('status', Conversacion::STATUS_ACTIVE);

        $anterior->refresh();
        $nueva = Conversacion::findOrFail($response->json('conversacion_id'));
        $this->assertSame(Conversacion::STATUS_CLOSED, $anterior->status);
        $this->assertNotNull($anterior->closed_at);
        $this->assertArrayNotHasKey(
            ReservaConversacionalService::CONTEXTO_BORRADOR,
            $anterior->contexto
        );
        $this->assertSame([], $nueva->contexto);
        $response->assertSessionHas('conversacion_id', $nueva->id);
    }

    public function test_historial_solo_procede_de_la_conversacion_activa(): void
    {
        $activa = Conversacion::factory()->create(['user_id' => $this->cliente->id]);
        $cerrada = Conversacion::factory()->create([
            'user_id' => $this->cliente->id,
            'status' => Conversacion::STATUS_CLOSED,
            'closed_at' => now(),
        ]);
        $this->mensaje($activa, MensajeChatbot::EMISOR_USUARIO, 'mensaje activo');
        $this->mensaje($cerrada, MensajeChatbot::EMISOR_USUARIO, 'mensaje histórico');

        $builder = app(HistorialConversacionalBuilder::class);

        $this->assertSame([
            ['role' => 'user', 'content' => 'mensaje activo'],
        ], $builder->construir($activa));
        $this->assertSame([], $builder->construir($cerrada));
    }

    public function test_historial_respeta_limites_configurados(): void
    {
        $conversacion = Conversacion::factory()->create(['user_id' => $this->cliente->id]);
        foreach (range(1, 8) as $numero) {
            $this->mensaje($conversacion, MensajeChatbot::EMISOR_USUARIO, "usuario {$numero}");
            $this->mensaje($conversacion, MensajeChatbot::EMISOR_BOT, "bot {$numero}");
        }

        $historial = app(HistorialConversacionalBuilder::class)->construir($conversacion);

        $this->assertCount(12, $historial);
        $this->assertCount(6, array_filter($historial, fn (array $item) => $item['role'] === 'user'));
        $this->assertCount(6, array_filter($historial, fn (array $item) => $item['role'] === 'assistant'));
        $this->assertSame('usuario 3', $historial[0]['content']);
        $this->assertSame('bot 8', $historial[11]['content']);
    }

    public function test_historial_excluye_mensajes_bloqueados_y_secretos(): void
    {
        $conversacion = Conversacion::factory()->create(['user_id' => $this->cliente->id]);
        $this->mensaje($conversacion, MensajeChatbot::EMISOR_USUARIO, 'hola');
        $this->mensaje(
            $conversacion,
            MensajeChatbot::EMISOR_USUARIO,
            '[Solicitud omitida por seguridad: credenciales]',
            DetectorIntencion::SOLICITUD_BLOQUEADA,
        );
        $this->mensaje(
            $conversacion,
            MensajeChatbot::EMISOR_BOT,
            'No puedo revelar secretos.',
            DetectorIntencion::SOLICITUD_BLOQUEADA,
        );
        $this->mensaje($conversacion, MensajeChatbot::EMISOR_BOT, 'api_key=secreto-no-incluir');
        $this->mensaje($conversacion, MensajeChatbot::EMISOR_BOT, '¡Hola! ¿En qué puedo ayudarte?');

        $historial = app(HistorialConversacionalBuilder::class)->construir($conversacion);
        $serializado = json_encode($historial, JSON_THROW_ON_ERROR);

        $this->assertCount(2, $historial);
        $this->assertStringNotContainsString('credenciales', $serializado);
        $this->assertStringNotContainsString('secreto-no-incluir', $serializado);
    }

    public function test_observabilidad_sigue_funcionando_con_el_ciclo_de_vida(): void
    {
        $this->postJson(route('chatbot.mensaje'), ['mensaje' => 'hola'])
            ->assertOk()
            ->assertJsonPath('intencion', DetectorIntencion::SALUDO);

        $this->assertDatabaseHas('chatbot_ejecuciones', [
            'external_status' => ResultadoProveedorNlp::ESTADO_PROVEEDOR_NO_CONFIGURADO,
            'external_attempted' => false,
            'fallback_local' => true,
            'engine_used' => ChatbotEjecucion::MOTOR_LOCAL,
            'detected_intent' => DetectorIntencion::SALUDO,
        ]);
    }

    public function test_normaliza_una_conversacion_historica_vencida_sin_borrar_su_historial(): void
    {
        $actividadHistorica = now()->subHours(2)->startOfSecond();
        $conversacion = Conversacion::factory()->create([
            'user_id' => $this->cliente->id,
            'contexto' => ['cancha_id' => 17],
            'status' => Conversacion::STATUS_ACTIVE,
            'last_activity_at' => null,
            'context_expires_at' => null,
            'closed_at' => null,
            'created_at' => $actividadHistorica,
            'updated_at' => $actividadHistorica,
        ]);
        $this->mensaje($conversacion, MensajeChatbot::EMISOR_USUARIO, 'mensaje histórico');

        $this->ejecutarNormalizacionHistorica();

        $conversacion->refresh();
        $this->assertSame(Conversacion::STATUS_EXPIRED, $conversacion->status);
        $this->assertTrue($conversacion->last_activity_at->equalTo($actividadHistorica));
        $this->assertTrue($conversacion->context_expires_at->equalTo(
            $actividadHistorica->copy()->addMinutes(30)
        ));
        $this->assertSame(['cancha_id' => 17], $conversacion->contexto);
        $this->assertSame(1, $conversacion->mensajes()->count());
    }

    public function test_normalizacion_conserva_activa_una_conversacion_historica_reciente(): void
    {
        $actividadReciente = now()->subMinutes(5)->startOfSecond();
        $conversacion = Conversacion::factory()->create([
            'user_id' => $this->cliente->id,
            'status' => Conversacion::STATUS_ACTIVE,
            'last_activity_at' => null,
            'context_expires_at' => null,
            'closed_at' => null,
            'created_at' => $actividadReciente,
            'updated_at' => $actividadReciente,
        ]);

        $this->ejecutarNormalizacionHistorica();

        $conversacion->refresh();
        $this->assertSame(Conversacion::STATUS_ACTIVE, $conversacion->status);
        $this->assertTrue($conversacion->last_activity_at->equalTo($actividadReciente));
        $this->assertTrue($conversacion->context_expires_at->isFuture());
    }

    private function ejecutarNormalizacionHistorica(): void
    {
        $migration = require database_path(
            'migrations/2026_09_18_000003_normalize_legacy_conversation_lifecycle.php'
        );
        $migration->up();
    }

    private function conBorrador(string $expiresAt): Conversacion
    {
        return Conversacion::factory()->create([
            'user_id' => $this->cliente->id,
            'contexto' => [
                'cancha_id' => 12,
                ReservaConversacionalService::CONTEXTO_BORRADOR => [
                    'estado' => 'recopilando',
                    'expires_at' => $expiresAt,
                ],
                'intencion_pendiente' => DetectorIntencion::HACER_RESERVA,
            ],
        ]);
    }

    private function mensaje(
        Conversacion $conversacion,
        string $emisor,
        string $contenido,
        ?string $intencion = null,
    ): MensajeChatbot {
        return $conversacion->mensajes()->create(compact('emisor', 'contenido', 'intencion'));
    }
}
