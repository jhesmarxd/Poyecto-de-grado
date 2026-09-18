<?php

namespace Tests\Unit;

use App\Services\Chatbot\AnalisisNlp;
use App\Services\Chatbot\DetectorIntencion;
use App\Services\Chatbot\ResultadoProveedorNlp;
use PHPUnit\Framework\TestCase;

class ResultadoProveedorNlpTest extends TestCase
{
    public function test_representa_un_exito_externo_sin_confundirlo_con_fallback(): void
    {
        $analisis = new AnalisisNlp(intencion: DetectorIntencion::SALUDO);
        $resultado = ResultadoProveedorNlp::exito(
            'gemini',
            'modelo-prueba',
            $analisis,
            $analisis->toArray(),
            25,
            200
        );

        $this->assertTrue($resultado->attempted);
        $this->assertTrue($resultado->succeeded);
        $this->assertSame(ResultadoProveedorNlp::ESTADO_EXITO, $resultado->status);
        $this->assertSame(DetectorIntencion::SALUDO, $resultado->analysis?->intencion);
    }

    public function test_distingue_proveedor_no_configurado_de_peticion_no_intentada(): void
    {
        $sinProveedor = ResultadoProveedorNlp::proveedorNoConfigurado();
        $borrador = ResultadoProveedorNlp::noIntentado('draft_active');

        $this->assertSame(ResultadoProveedorNlp::ESTADO_PROVEEDOR_NO_CONFIGURADO, $sinProveedor->status);
        $this->assertSame(ResultadoProveedorNlp::ESTADO_NO_INTENTADO, $borrador->status);
        $this->assertSame('draft_active', $borrador->errorCode);
        $this->assertFalse($sinProveedor->attempted);
        $this->assertFalse($borrador->attempted);
    }
}
