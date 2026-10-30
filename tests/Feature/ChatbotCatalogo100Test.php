<?php

namespace Tests\Feature;

use App\Models\Balon;
use App\Models\Cancha;
use App\Models\Conversacion;
use App\Services\Chatbot\DetectorIntencion;
use App\Services\ChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChatbotCatalogo100Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 17:00:00', 'America/La_Paz'));
        parent::setUp();
        config()->set('chatbot.provider', null);
        config()->set('chatbot.api_key', null);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[DataProvider('catalogo')]
    public function test_los_cien_casos_producen_una_respuesta_segura_sin_reservar_sin_confirmacion(
        int $numero,
        array $mensajes,
        bool $conContexto = false,
    ): void {
        $canchas = $this->crearEscenarioBase();
        $contexto = $conContexto ? [
            'fecha' => '2026-10-02',
            'hora' => '18:00:00',
            'hora_fin' => '20:00:00',
            'intencion_pendiente' => DetectorIntencion::CONSULTAR_DISPONIBILIDAD,
            'resultado_disponibilidad' => [
                'estado' => 'disponible',
                'fecha' => '2026-10-02',
                'hora_inicio' => '18:00:00',
                'hora_fin' => '20:00:00',
                'cancha_ids' => $canchas->pluck('id')->all(),
                'alternativas' => [],
            ],
        ] : [];
        $conversacion = Conversacion::create(['contexto' => $contexto]);
        $bot = app(ChatbotService::class);
        $ultima = null;

        foreach ($mensajes as $mensaje) {
            $ultima = $bot->responder($mensaje, $conversacion->fresh());
            $this->assertIsString($ultima['respuesta'], "Caso {$numero}: respuesta inválida.");
            $this->assertNotSame('', trim($ultima['respuesta']), "Caso {$numero}: respuesta vacía.");
            $this->assertArrayHasKey('intencion', $ultima, "Caso {$numero}: falta intención.");
        }

        $this->assertDatabaseCount('reservas', 0);
        $this->assertCount(count($mensajes) * 2, $conversacion->mensajes()->get());
        $this->assertIsArray($conversacion->fresh()->contexto ?? []);

        if (in_array($numero, [96, 97], true)) {
            $this->assertSame('20:00:00', $conversacion->fresh()->contextoDe('hora'));
            $this->assertSame('22:00:00', $conversacion->fresh()->contextoDe('hora_fin'));
        }
        if ($numero === 95) {
            $this->assertNull($conversacion->fresh()->contextoDe('reserva_borrador'));
        }
        if ($numero === 99) {
            $this->assertSame('confirmacion', $conversacion->fresh()->contextoDe('reserva_borrador')['estado'] ?? null);
        }
    }

    private function crearEscenarioBase()
    {
        $canchas = collect([
            Cancha::factory()->create(['nombre' => 'Cancha 1']),
            Cancha::factory()->create(['nombre' => 'Cancha 2']),
        ]);
        foreach ($canchas as $cancha) {
            foreach (range(1, 6) as $dia) {
                $cursor = Carbon::parse('17:00:00');
                $fin = Carbon::parse('22:00:00');
                while ($cursor->lt($fin)) {
                    $cancha->horarios()->create([
                        'dia_semana' => $dia,
                        'hora_inicio' => $cursor->format('H:i:s'),
                        'hora_fin' => $cursor->copy()->addMinutes(30)->format('H:i:s'),
                        'disponible' => true,
                    ]);
                    $cursor->addMinutes(30);
                }
            }
        }
        Balon::create([
            'tipo' => Balon::TIPO_ECONOMICO,
            'marca' => 'Wally',
            'nombre' => 'Económico 1',
            'precio_por_hora' => 25,
        ]);

        return $canchas;
    }

    public static function catalogo(): array
    {
        $casos = [
            1 => [['¿Hay cancha hoy de 4 PM a 6 PM?'], false],
            2 => [['¿Hay cancha de 5 PM a 7 PM?'], false],
            3 => [['¿La cancha 1 está libre de 6 PM a 8 PM?'], false],
            4 => [['¿La cancha 2 está libre a las 7 PM?'], false],
            5 => [['¿Qué tienen disponible hoy?'], false],
            6 => [['Quiero jugar esta noche.'], false],
            7 => [['Algo para las 6.'], false],
            8 => [['Desde las 6 hasta las 8.'], false],
            9 => [['¿Cancha?'], false],
            10 => [['¿Tienen libre ahorita?'], false],
            11 => [['¿Hay de 5 PM a 7 PM?'], false],
            12 => [['¿Hay cancha mañana de 6 PM a 8 PM?'], false],
            13 => [['Quiero la cancha 2 mañana de 5 PM a 7 PM.'], false],
            14 => [['¿Cuál me recomiendas?'], true],
            15 => [['Cualquiera.'], true],
            16 => [['La que esté libre.'], true],
            17 => [['Me da igual.'], true],
            18 => [['¿Las dos?'], true],
            19 => [['¿Puedo elegir después?'], true],
            20 => [['Gracias, luego veo.'], true],
            21 => [['¿Hay de 5 PM a 7 PM?'], true],
            22 => [['Quiero la cancha 2.'], true],
            23 => [['Solo quiero la 2.'], true],
            24 => [['Entonces nada.'], true],
            25 => [['Bueno, dame la 1.'], true],
            26 => [['¿A qué hora se libera la 2?'], true],
            27 => [['Mejor espero.'], true],
            28 => [['¿La 1 está libre todo ese tiempo?'], true],
            29 => [['¿Seguro?'], true],
            30 => [[], true],
            31 => [['¿Hay de 4 PM a 6 PM?'], true],
            32 => [['Quiero la 1.'], true],
            33 => [['No quiero la 2.'], true],
            34 => [['¿Cuándo queda libre la 1?'], true],
            35 => [['Bueno entonces la 2.'], true],
            36 => [['¿La 2 está libre hasta las 7 PM?'], true],
            37 => [['¿Hasta qué hora está libre?'], true],
            38 => [['La quiero solo hasta las 6 PM.'], true],
            39 => [['¿Y si comienzo media hora después?'], true],
            40 => [['Mejor otro día.'], true],
            41 => [['Quiero de 4 PM a 6 PM.'], true],
            42 => [['Sí, una hora está bien.'], true],
            43 => [['No, necesito las dos horas.'], true],
            44 => [['¿Qué hay después?'], true],
            45 => [['¿Y antes?'], true],
            46 => [['¿Y la cancha 1?'], true],
            47 => [['¿Hay aunque sea una hora?'], true],
            48 => [['¿Y una hora y media?'], true],
            49 => [['Entonces déjalo.'], true],
            50 => [['Para la próxima nomás.'], true],
            51 => [['¿Hay de 6 PM a 8 PM?'], true],
            52 => [['¿Nada?'], true],
            53 => [['Qué pena.'], true],
            54 => [['No gracias.'], true],
            55 => [['¿Mañana sí?'], true],
            56 => [['Entonces de 8 PM a 10 PM.'], true],
            57 => [['Dame lo más cercano.'], true],
            58 => [['¿Qué hora está libre primero?'], true],
            59 => [['No tengo otra hora.'], true],
            60 => [['Bueno, chau.'], true],
            61 => [['¿Hay mañana de 5 PM a 7 PM?'], false],
            62 => [['¿Hay pasado mañana?'], false],
            63 => [['El viernes de 8 PM a 10 PM.'], false],
            64 => [['El 10 de octubre de 6 PM a 8 PM.'], false],
            65 => [['Hoy.'], false],
            66 => [['Mañana a las 7.'], false],
            67 => [['Para este sábado.'], false],
            68 => [['Para el otro sábado.'], false],
            69 => [['Para fin de mes.'], false],
            70 => [['Para diciembre.'], false],
            71 => [['Quiero reservar dentro de un mes.'], false],
            72 => [['Pero quiero dejarlo pagado desde ahora.'], true],
            73 => [['¿Por qué?'], true],
            74 => [['Entonces, ¿cuándo puedo reservar?'], true],
            75 => [['¿Puedes guardármela?'], true],
            76 => [['Quiero para ayer.'], false],
            77 => [['Para hoy a las 10 AM.'], false],
            78 => [['De 4 PM a 6 PM.'], false],
            79 => [['Para una fecha que todavía no está habilitada.'], false],
            80 => [['Quiero para dentro de 20 días.'], false],
            81 => [['Quiero de 6 AM a 7 AM.'], false],
            82 => [['Quiero de 10 PM a 11 PM.'], false],
            83 => [['De 9 PM a 11 PM.'], false],
            84 => [['¿Cuál es la última hora disponible?'], true],
            85 => [['¿Desde qué hora abren mañana?'], false],
            86 => [['¿Atienden el domingo?'], false],
            87 => [['Entonces, ¿el domingo está cerrado?'], true],
            88 => [['¿Y el lunes?'], true],
            89 => [['Quiero exactamente cuando abran.'], true],
            90 => [['Quiero hasta que cierren.'], true],
            91 => [['Quiero Cancha 1 mañana de 5 PM a 7 PM.', 'Mejor la 2.'], false],
            92 => [['¿Hay hoy de 5 PM a 7 PM?', 'Mejor mañana.'], false],
            93 => [['¿Hay de 5 PM a 7 PM?', 'Mejor de 6 PM a 8 PM.'], false],
            94 => [['¿Hay mañana de 5 PM a 7 PM?', 'Mejor pasado mañana de 7 PM a 9 PM en la 2.'], false],
            95 => [['¿Hay mañana de 5 PM a 7 PM?', 'No, olvida todo. Empecemos de nuevo.'], false],
            96 => [['¿Hay de 8 a 10?', 'PM.'], false],
            97 => [['¿Hay de 8 a 10?', 'PM.'], false],
            98 => [['Quiero de 18:00 a 8 PM.'], false],
            99 => [['Quiero reservar mañana de 7 PM a 9 PM en la cancha 1.', 'Quiero el económico.'], false],
            100 => [['¿Hay cancha de 7 PM a 9 PM?', 'No, está bien. Para la siguiente nomás.'], false],
        ];

        $datos = [];
        foreach ($casos as $numero => [$mensajes, $contexto]) {
            $datos['caso '.str_pad((string) $numero, 3, '0', STR_PAD_LEFT)] = [$numero, $mensajes, $contexto];
        }

        return $datos;
    }
}
