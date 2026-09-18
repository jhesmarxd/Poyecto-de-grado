<?php

namespace Tests\Unit;

use App\Services\Chatbot\DetectorIntencion;
use PHPUnit\Framework\TestCase;

class ChatbotMotorTest extends TestCase
{
    private DetectorIntencion $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new DetectorIntencion;
    }

    public function test_detecta_el_saludo(): void
    {
        $this->assertSame(DetectorIntencion::SALUDO, $this->detector->detectar('hola buenas tardes'));
    }

    public function test_detecta_la_despedida(): void
    {
        $this->assertSame(DetectorIntencion::DESPEDIDA, $this->detector->detectar('chao hasta luego'));
    }

    public function test_detecta_la_ayuda(): void
    {
        $this->assertSame(DetectorIntencion::AYUDA, $this->detector->detectar('qué puedes hacer'));
    }

    public function test_detecta_el_contacto(): void
    {
        $this->assertSame(DetectorIntencion::CONTACTO, $this->detector->detectar('cuál es su teléfono y dirección'));
    }

    public function test_detecta_consultar_horarios(): void
    {
        $this->assertSame(DetectorIntencion::CONSULTAR_HORARIOS, $this->detector->detectar('¿qué horarios tienen?'));
    }

    public function test_detecta_consultar_precios(): void
    {
        $this->assertSame(DetectorIntencion::CONSULTAR_PRECIOS, $this->detector->detectar('cuánto cuesta la cancha'));
    }

    public function test_detecta_disponibilidad_antes_que_horarios(): void
    {
        // "horario libre" debe priorizar disponibilidad sobre horarios
        $this->assertSame(DetectorIntencion::CONSULTAR_DISPONIBILIDAD, $this->detector->detectar('hay horario libre mañana'));
    }

    public function test_detecta_hacer_reserva(): void
    {
        $this->assertSame(DetectorIntencion::HACER_RESERVA, $this->detector->detectar('quiero reservar una cancha'));
    }

    public function test_detecta_modificar_reserva_antes_que_consultarla(): void
    {
        $this->assertSame(
            DetectorIntencion::MODIFICAR_RESERVA,
            $this->detector->detectar('quiero cambiar la fecha de mi reserva 15')
        );
    }

    public function test_detecta_cancelar_reserva_antes_que_consultarla(): void
    {
        $this->assertSame(
            DetectorIntencion::CANCELAR_RESERVA,
            $this->detector->detectar('quiero cancelar mi reserva #15')
        );
    }

    public function test_detecta_consultar_estado_reserva_antes_que_hacer_reserva(): void
    {
        // "mi reserva" debe priorizar el estado sobre la creación
        $this->assertSame(DetectorIntencion::CONSULTAR_ESTADO_RESERVA, $this->detector->detectar('¿cuál es el estado de mi reserva?'));
    }

    public function test_sin_coincidencia_devuelve_no_detectada(): void
    {
        $this->assertSame(DetectorIntencion::NO_DETECTADA, $this->detector->detectar('asdfghjkl'));
    }

    public function test_la_normalizacion_ignora_acentos_y_mayusculas(): void
    {
        $this->assertSame(DetectorIntencion::CONSULTAR_PRECIOS, $this->detector->detectar('CUÁNTO CUESTA'));
    }

    public function test_el_mensaje_vacio_no_se_detecta(): void
    {
        $this->assertSame(DetectorIntencion::NO_DETECTADA, $this->detector->detectar('   '));
    }
}
