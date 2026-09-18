<?php

namespace App\Services\Chatbot;

final readonly class ResultadoProveedorNlp
{
    public const ESTADO_PROVEEDOR_NO_CONFIGURADO = 'provider_not_configured';

    public const ESTADO_NO_INTENTADO = 'not_attempted';

    public const ESTADO_EXITO = 'success';

    public const ESTADO_TIMEOUT = 'timeout';

    public const ESTADO_CONEXION = 'connection_error';

    public const ESTADO_HTTP_400 = 'http_400';

    public const ESTADO_HTTP_401 = 'http_401';

    public const ESTADO_HTTP_429 = 'http_429';

    public const ESTADO_HTTP_5XX = 'http_5xx';

    public const ESTADO_HTTP_ERROR = 'http_error';

    public const ESTADO_RESPUESTA_VACIA = 'empty_response';

    public const ESTADO_JSON_INVALIDO = 'invalid_json';

    public const ESTADO_ANALISIS_INVALIDO = 'invalid_structured_analysis';

    public const ESTADO_PROVEEDOR_NO_SOPORTADO = 'unsupported_provider';

    public const ESTADO_CLAVE_AUSENTE = 'missing_api_key';

    public const ESTADO_ERROR_INESPERADO = 'unexpected_error';

    /**
     * @param  array<string, mixed>|null  $structuredParameters
     */
    public function __construct(
        public string $status,
        public bool $attempted,
        public bool $succeeded,
        public ?string $provider = null,
        public ?string $model = null,
        public ?int $latencyMs = null,
        public ?int $httpStatus = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public ?AnalisisNlp $analysis = null,
        public ?array $structuredParameters = null,
    ) {}

    public static function proveedorNoConfigurado(): self
    {
        return new self(
            status: self::ESTADO_PROVEEDOR_NO_CONFIGURADO,
            attempted: false,
            succeeded: false,
        );
    }

    public static function noIntentado(string $motivo): self
    {
        return new self(
            status: self::ESTADO_NO_INTENTADO,
            attempted: false,
            succeeded: false,
            errorCode: $motivo,
        );
    }

    /** @param array<string, mixed> $structuredParameters */
    public static function exito(
        string $provider,
        string $model,
        AnalisisNlp $analysis,
        array $structuredParameters,
        int $latencyMs = 0,
        int $httpStatus = 200,
    ): self {
        return new self(
            status: self::ESTADO_EXITO,
            attempted: true,
            succeeded: true,
            provider: $provider,
            model: $model,
            latencyMs: $latencyMs,
            httpStatus: $httpStatus,
            analysis: $analysis,
            structuredParameters: $structuredParameters,
        );
    }

    /** @param array<string, mixed>|null $structuredParameters */
    public static function fallo(
        string $status,
        string $provider,
        string $model,
        bool $attempted,
        ?int $latencyMs = null,
        ?int $httpStatus = null,
        ?string $errorMessage = null,
        ?array $structuredParameters = null,
    ): self {
        return new self(
            status: $status,
            attempted: $attempted,
            succeeded: false,
            provider: $provider,
            model: $model,
            latencyMs: $latencyMs,
            httpStatus: $httpStatus,
            errorCode: $status,
            errorMessage: $errorMessage,
            structuredParameters: $structuredParameters,
        );
    }
}
