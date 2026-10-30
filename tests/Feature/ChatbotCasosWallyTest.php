<?php

namespace Tests\Feature;

use App\Models\Cancha;
use App\Models\Conversacion;
use App\Models\Reserva;
use App\Models\User;
use App\Services\Chatbot\ExtractorEntidades;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChatbotCasosWallyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function crearCanchasConRango(string $fecha): array
    {
        $canchas = [
            Cancha::factory()->create(['nombre' => 'Cancha 1']),
            Cancha::factory()->create(['nombre' => 'Cancha 2']),
        ];
        foreach ($canchas as $cancha) {
            foreach ([20, 20.5, 21, 21.5] as $hora) {
                $horas = (int) floor($hora);
                $minutos = $hora === $horas ? 0 : 30;
                $inicio = sprintf('%02d:%02d:00', $horas, $minutos);
                $fin = Carbon::parse($inicio)->addMinutes(30)->format('H:i:s');
                $cancha->horarios()->create([
                    'dia_semana' => Carbon::parse($fecha)->dayOfWeekIso,
                    'hora_inicio' => $inicio,
                    'hora_fin' => $fin,
                    'disponible' => true,
                ]);
            }
        }

        return $canchas;
    }

    #[DataProvider('casosTemporales')]
    public function test_el_extractor_cubre_las_expresiones_temporales_del_catalogo(
        string $mensaje,
        ?string $fecha,
        ?string $inicio,
        ?string $fin,
        ?int $duracion,
    ): void {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:10:00', 'America/La_Paz'));
        $extractor = app(ExtractorEntidades::class);
        $rango = $extractor->rangoHoras($mensaje);

        $this->assertSame($fecha, $extractor->fecha($mensaje));
        $this->assertSame($inicio, $rango['inicio'] ?? $extractor->hora($mensaje));
        $this->assertSame($fin, $rango['fin'] ?? $extractor->horaFinAislada($mensaje));
        $this->assertSame($duracion, $extractor->duracionMinutos($mensaje));
    }

    public static function casosTemporales(): array
    {
        return [
            'dentro de veinte dias' => ['Quiero para dentro de 20 días', '2026-10-21', null, null, null],
            'dentro de un mes' => ['Quiero reservar dentro de un mes', '2026-11-01', null, null, null],
            'fin de mes' => ['Para fin de mes', '2026-10-31', null, null, null],
            'otro sabado' => ['Para el otro sábado', '2026-10-10', null, null, null],
            'mezcla formatos' => ['Quiero de 18:00 a 8 PM', null, '18:00:00', '20:00:00', null],
            'formato veinticuatro horas' => ['Hay de 13:00 a 15:00', null, '13:00:00', '15:00:00', null],
            'hora y media' => ['Quiero una hora y media', null, null, null, 90],
            'dos horas' => ['Necesito dos horas', null, null, null, 120],
            'ahorita' => ['¿Tienen libre ahorita?', null, '12:30:00', null, null],
            'final aislado' => ['Solo hasta las 6 PM', null, '18:00:00', '18:00:00', null],
        ];
    }

    public function test_un_rango_sin_am_pm_pregunta_y_la_respuesta_pm_continua_con_ambas_canchas(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00', 'America/La_Paz'));
        $fecha = now()->addDay()->toDateString();
        [$cancha1, $cancha2] = $this->crearCanchasConRango($fecha);
        $conversacion = Conversacion::create(['contexto' => []]);
        $bot = app(ChatbotService::class);

        $pregunta = $bot->responder('¿Hay cancha mañana de 8 a 10?', $conversacion);
        $this->assertStringContainsString('AM o PM', $pregunta['respuesta']);
        $this->assertSame('horario_ambiguo', $pregunta['estado']);

        $respuesta = $bot->responder('PM', $conversacion->fresh());
        $this->assertStringContainsString($cancha1->nombre, $respuesta['respuesta']);
        $this->assertStringContainsString($cancha2->nombre, $respuesta['respuesta']);
        $this->assertSame('20:00:00', $conversacion->fresh()->contextoDe('hora'));
        $this->assertSame('22:00:00', $conversacion->fresh()->contextoDe('hora_fin'));
    }

    public function test_un_horario_con_pm_explicito_no_vuelve_a_preguntar(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00', 'America/La_Paz'));
        $fecha = now()->addDay()->toDateString();
        $this->crearCanchasConRango($fecha);

        $respuesta = app(ChatbotService::class)->responder(
            '¿Hay cancha mañana de 8 PM a 10 PM?',
            Conversacion::create(['contexto' => []]),
        );

        $this->assertStringNotContainsString('AM o PM', $respuesta['respuesta']);
        $this->assertStringContainsString('Cancha 1', $respuesta['respuesta']);
        $this->assertStringContainsString('Cancha 2', $respuesta['respuesta']);
        $this->assertSame('disponible', $respuesta['resultado']['estado']);
        $this->assertCount(2, $respuesta['resultado']['cancha_ids']);
    }

    public function test_olvida_todo_limpia_el_estado_temporal_sin_crear_reserva(): void
    {
        $conversacion = Conversacion::create(['contexto' => [
            'fecha' => now()->addDay()->toDateString(),
            'hora' => '18:00:00',
            'reserva_borrador' => ['estado' => 'recopilando'],
        ]]);

        $respuesta = app(ChatbotService::class)->responder('No, olvida todo. Empecemos de nuevo.', $conversacion);

        $this->assertStringContainsString('olvidé los datos temporales', $respuesta['respuesta']);
        $this->assertNull($conversacion->fresh()->contextoDe('reserva_borrador'));
        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_un_dia_de_semana_ya_iniciado_se_resuelve_a_la_semana_siguiente(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 20:00:00', 'America/La_Paz'));
        $fecha = '2026-10-12';
        $this->crearCanchasConRango($fecha);

        $respuesta = app(ChatbotService::class)->responder(
            '¿Hay cancha el lunes de 8 PM a 10 PM?',
            Conversacion::create(['contexto' => []]),
        );

        $this->assertSame($fecha, $respuesta['resultado']['fecha']);
    }

    public function test_cambiar_solo_la_fecha_conserva_el_rango_y_reconsulta(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00', 'America/La_Paz'));
        $this->crearCanchasConRango('2026-10-02');
        $this->crearCanchasConRango('2026-10-05');
        $conversacion = Conversacion::create(['contexto' => []]);
        $bot = app(ChatbotService::class);

        $bot->responder('¿Hay mañana de 8 PM a 10 PM?', $conversacion);
        $this->assertSame('consultar_disponibilidad', $conversacion->fresh()->contextoDe('intencion_pendiente'));
        $respuesta = $bot->responder('¿Y el lunes?', $conversacion->fresh());

        $this->assertSame('2026-10-05', $conversacion->fresh()->contextoDe('fecha'));
        $this->assertSame('20:00:00', $conversacion->fresh()->contextoDe('hora'));
        $this->assertSame('22:00:00', $conversacion->fresh()->contextoDe('hora_fin'));
        $this->assertSame('consultar_disponibilidad', $respuesta['intencion']);
        $this->assertStringContainsString('20:00', $respuesta['respuesta']);
    }

    public function test_seguro_reconsulta_la_base_de_datos(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00', 'America/La_Paz'));
        $fecha = '2026-10-02';
        $canchas = $this->crearCanchasConRango($fecha);
        $conversacion = Conversacion::create(['contexto' => []]);
        $bot = app(ChatbotService::class);
        $bot->responder('¿Hay mañana de 8 PM a 10 PM?', $conversacion);

        foreach ($canchas as $cancha) {
            $horario = $cancha->horarios()->where('hora_inicio', '20:00:00')->firstOrFail();
            Reserva::create([
                'user_id' => User::factory()->create()->id,
                'cancha_id' => $cancha->id,
                'horario_id' => $horario->id,
                'fecha' => $fecha,
                'hora_inicio_reserva' => '20:00:00',
                'hora_fin_reserva' => '22:00:00',
                'duracion_minutos' => 120,
                'monto_total' => $cancha->precio_por_hora * 2,
                'tipo_balon' => Reserva::BALON_PROPIO,
                'estado' => Reserva::ESTADO_CONFIRMADA,
            ]);
        }

        $respuesta = $bot->responder('¿Seguro?', $conversacion->fresh());

        $this->assertStringContainsString('verificar nuevamente', $respuesta['respuesta']);
        $this->assertNotContains($respuesta['estado'], ['disponible', 'una_cancha']);
    }

    public function test_que_hay_despues_busca_un_bloque_continuo_real(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00', 'America/La_Paz'));
        $fecha = '2026-10-02';
        $this->crearCanchasConRango($fecha);
        $conversacion = Conversacion::create(['contexto' => []]);
        $bot = app(ChatbotService::class);

        $bot->responder('¿Hay mañana de 6 PM a 8 PM?', $conversacion);
        $respuesta = $bot->responder('¿Qué hay después?', $conversacion->fresh());

        $this->assertSame('alternativa', $respuesta['estado']);
        $this->assertStringContainsString('20:00', $respuesta['respuesta']);
        $this->assertStringContainsString('22:00', $respuesta['respuesta']);
        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_aceptar_una_hora_toma_y_revalida_la_alternativa_parcial(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00', 'America/La_Paz'));
        $fecha = '2026-10-02';
        $canchas = $this->crearCanchasConRango($fecha);
        $usuario = User::factory()->create();
        foreach ($canchas as $cancha) {
            $horario = $cancha->horarios()->where('hora_inicio', '20:00:00')->firstOrFail();
            Reserva::create([
                'user_id' => $usuario->id,
                'cancha_id' => $cancha->id,
                'horario_id' => $horario->id,
                'fecha' => $fecha,
                'hora_inicio_reserva' => '20:00:00',
                'hora_fin_reserva' => '21:00:00',
                'duracion_minutos' => 60,
                'monto_total' => $cancha->precio_por_hora,
                'tipo_balon' => Reserva::BALON_PROPIO,
                'estado' => Reserva::ESTADO_CONFIRMADA,
            ]);
        }
        $conversacion = Conversacion::create(['contexto' => []]);
        $bot = app(ChatbotService::class);

        $parcial = $bot->responder('¿Hay mañana de 8 PM a 10 PM?', $conversacion);
        $this->assertSame('parcial', $parcial['resultado']['estado']);

        $aceptada = $bot->responder('Sí, una hora está bien', $conversacion->fresh());

        $this->assertSame('21:00:00', $conversacion->fresh()->contextoDe('hora'));
        $this->assertSame('22:00:00', $conversacion->fresh()->contextoDe('hora_fin'));
        $this->assertStringContainsString('disponible', $aceptada['respuesta']);
        $this->assertDatabaseCount('reservas', 2);
    }

    public function test_ultima_hora_disponible_calcula_el_ultimo_bloque_continuo_real(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00', 'America/La_Paz'));
        $fecha = '2026-10-02';
        $this->crearCanchasConRango($fecha);
        $conversacion = Conversacion::create(['contexto' => []]);

        $respuesta = app(ChatbotService::class)->responder('¿Cuál es la última hora disponible mañana?', $conversacion);

        $this->assertSame('21:00:00', $conversacion->fresh()->contextoDe('hora'));
        $this->assertSame('22:00:00', $conversacion->fresh()->contextoDe('hora_fin'));
        $this->assertStringContainsString('21:00', $respuesta['respuesta']);
        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_una_reserva_vaga_pide_hora_antes_que_cancha(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00', 'America/La_Paz'));
        Cancha::factory()->create(['nombre' => 'Cancha 1']);

        $respuesta = app(ChatbotService::class)->responder(
            'Quiero jugar esta noche',
            Conversacion::create(['contexto' => []]),
        );

        $this->assertStringContainsString('qué hora aproximadamente', $respuesta['respuesta']);
        $this->assertDatabaseCount('reservas', 0);
    }

    public function test_consultar_un_domingo_lee_el_horario_semanal_cerrado(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00', 'America/La_Paz'));

        $respuesta = app(ChatbotService::class)->responder(
            '¿Atienden el domingo?',
            Conversacion::create(['contexto' => []]),
        );

        $this->assertStringContainsString('cerrado', $respuesta['respuesta']);
    }
}
