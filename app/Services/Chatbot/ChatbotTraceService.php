<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotEjecucion;
use App\Models\MensajeChatbot;
use Illuminate\Support\Facades\Log;
use Throwable;

class ChatbotTraceService
{
    /**
     * Lista cerrada para impedir que credenciales, headers, cookies, prompts o
     * cuerpos HTTP completos lleguen a la tabla de observabilidad.
     */
    private const PARAMETROS_PERMITIDOS = [
        'intencion',
        'reserva_id',
        'cancha_id',
        'cancha_referencia',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'duracion_minutos',
        'balon_id',
        'balon_referencia',
    ];

    /**
     * @param  array<string, mixed>  $parametros
     */
    public function registrar(
        MensajeChatbot $mensaje,
        ResultadoProveedorNlp $resultado,
        string $motor,
        ?string $intencion,
        array $parametros = [],
    ): ?ChatbotEjecucion {
        try {
            return ChatbotEjecucion::query()->create([
                'mensaje_chatbot_id' => $mensaje->id,
                'conversacion_id' => $mensaje->conversacion_id,
                'provider' => $resultado->provider,
                'model' => $resultado->model,
                'external_status' => $resultado->status,
                'external_attempted' => $resultado->attempted,
                'external_used' => $resultado->succeeded && $resultado->analysis !== null,
                'fallback_local' => $motor === ChatbotEjecucion::MOTOR_LOCAL,
                'engine_used' => $motor,
                'external_latency_ms' => $resultado->latencyMs,
                'external_http_status' => $resultado->httpStatus,
                'external_error_code' => $resultado->errorCode,
                'external_error_message' => $this->mensajeErrorSeguro($resultado),
                'detected_intent' => $intencion,
                'structured_parameters' => $this->sanitizarParametros(
                    $resultado->structuredParameters ?? $parametros
                ),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Chatbot observabilidad: no se pudo persistir la traza.', [
                'conversation_id' => $mensaje->conversacion_id,
                'message_id' => $mensaje->id,
                'exception' => $exception::class,
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $parametros
     * @return array<string, bool|float|int|string|null>|null
     */
    public function sanitizarParametros(array $parametros): ?array
    {
        $limpios = [];

        foreach (self::PARAMETROS_PERMITIDOS as $clave) {
            if (! array_key_exists($clave, $parametros)) {
                continue;
            }

            $valor = $parametros[$clave];
            if (is_null($valor) || is_bool($valor) || is_int($valor) || is_float($valor) || is_string($valor)) {
                $limpios[$clave] = is_string($valor) ? $this->sanitizarTexto($valor) : $valor;
            }
        }

        return $limpios === [] ? null : $limpios;
    }

    private function sanitizarTexto(string $valor): string
    {
        $valor = mb_substr($valor, 0, 200);
        $patronesSensibles = [
            '/\bapi[\s_-]*key\b/i',
            '/\b(access|refresh)[\s_-]*token\b/i',
            '/\b(password|contrasena|contraseña|secret|cookie|authorization|bearer)\b/i',
            '/(?:^|\s)\.env(?:\s|$)/i',
            '/\bvariables?[\s_-]+de[\s_-]+entorno\b/i',
        ];

        foreach ($patronesSensibles as $patron) {
            if (preg_match($patron, $valor) === 1) {
                return '[REDACTED]';
            }
        }

        return $valor;
    }

    private function mensajeErrorSeguro(ResultadoProveedorNlp $resultado): ?string
    {
        if (! $resultado->errorMessage) {
            return null;
        }

        return match ($resultado->status) {
            ResultadoProveedorNlp::ESTADO_TIMEOUT => 'La solicitud al proveedor excedió el tiempo máximo.',
            ResultadoProveedorNlp::ESTADO_CONEXION => 'No se pudo establecer conexión con el proveedor.',
            ResultadoProveedorNlp::ESTADO_HTTP_400 => 'El proveedor rechazó la estructura de la solicitud.',
            ResultadoProveedorNlp::ESTADO_HTTP_401 => 'El proveedor rechazó la autenticación configurada.',
            ResultadoProveedorNlp::ESTADO_HTTP_429 => 'El proveedor limitó temporalmente las solicitudes.',
            ResultadoProveedorNlp::ESTADO_HTTP_5XX => 'El proveedor presentó un error temporal del servidor.',
            ResultadoProveedorNlp::ESTADO_RESPUESTA_VACIA => 'El proveedor devolvió una respuesta estructurada vacía.',
            ResultadoProveedorNlp::ESTADO_JSON_INVALIDO => 'El proveedor devolvió JSON inválido.',
            ResultadoProveedorNlp::ESTADO_ANALISIS_INVALIDO => 'El proveedor devolvió un análisis estructurado inválido.',
            ResultadoProveedorNlp::ESTADO_CLAVE_AUSENTE => 'El proveedor no tiene una credencial configurada.',
            ResultadoProveedorNlp::ESTADO_PROVEEDOR_NO_SOPORTADO => 'El proveedor configurado no está soportado.',
            default => 'El proveedor no produjo un análisis utilizable.',
        };
    }
}
