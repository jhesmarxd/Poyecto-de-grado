<?php

namespace Tests\Unit;

use App\Services\Chatbot\AnalisisNlp;
use App\Services\Chatbot\DetectorIntencion;
use PHPUnit\Framework\TestCase;

class AnalisisNlpTest extends TestCase
{
    public function test_valida_y_normaliza_la_salida_estructurada(): void
    {
        $analisis = AnalisisNlp::desdeArray([
            'intencion' => DetectorIntencion::HACER_RESERVA,
            'reserva_id' => 18,
            'cancha_referencia' => 'Cancha 1',
            'fecha' => '2026-09-12',
            'hora_inicio' => '17:00',
            'hora_fin' => '19:00',
            'duracion_minutos' => 120,
            'balon_referencia' => null,
        ]);

        $this->assertSame('17:00:00', $analisis?->horaInicio);
        $this->assertSame('19:00:00', $analisis?->horaFin);
        $this->assertSame(18, $analisis?->reservaId);
    }

    public function test_rechaza_una_intencion_fuera_del_catalogo(): void
    {
        $this->assertNull(AnalisisNlp::desdeArray(['intencion' => 'inventar_disponibilidad']));
    }

    public function test_descarta_entidades_invalidas_sin_aceptarlas_como_datos_reales(): void
    {
        $analisis = AnalisisNlp::desdeArray([
            'intencion' => DetectorIntencion::CONSULTAR_DISPONIBILIDAD,
            'fecha' => '2026-99-99',
            'hora_inicio' => '28:00',
            'hora_fin' => '10:00',
            'duracion_minutos' => 900,
        ]);

        $this->assertNotNull($analisis);
        $this->assertNull($analisis->fecha);
        $this->assertNull($analisis->horaInicio);
        $this->assertNull($analisis->duracionMinutos);
    }
}
