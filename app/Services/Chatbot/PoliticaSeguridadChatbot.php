<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Str;

final class PoliticaSeguridadChatbot
{
    public const CATEGORIA_CREDENCIALES = 'credenciales';

    public const CATEGORIA_INYECCION = 'inyeccion_prompt';

    public const CATEGORIA_SQL = 'sql';

    public const CATEGORIA_PRIVACIDAD = 'privacidad';

    public const CATEGORIA_PRIVILEGIOS = 'privilegios';

    /**
     * @return array{categoria: string, respuesta: string}|null
     */
    public function evaluar(string $mensaje): ?array
    {
        $texto = $this->normalizar($mensaje);

        if ($texto === '' || $this->esAyudaConPasswordPropio($texto)) {
            return null;
        }

        if ($this->esInyeccionDePrompt($texto)) {
            return $this->bloqueo(
                self::CATEGORIA_INYECCION,
                'No puedo ignorar mis reglas, revelar instrucciones internas ni cambiar mis controles de seguridad. Puedo ayudarte con canchas, horarios, precios y reservas.'
            );
        }

        if ($this->esEscalamientoDePrivilegios($texto)) {
            return $this->bloqueo(
                self::CATEGORIA_PRIVILEGIOS,
                'No puedo asumir el rol de administrador, cambiar permisos ni eludir la autorización del sistema.'
            );
        }

        if ($this->esSolicitudSql($texto)) {
            return $this->bloqueo(
                self::CATEGORIA_SQL,
                'No puedo ejecutar ni generar consultas SQL sobre el sistema. Puedo realizar únicamente las operaciones de reserva autorizadas.'
            );
        }

        if ($this->esSolicitudDeDatosPrivados($texto)) {
            return $this->bloqueo(
                self::CATEGORIA_PRIVACIDAD,
                'No puedo compartir datos privados, cuentas ni reservas de otras personas. Solo puedo consultar la información permitida de tu propia cuenta.'
            );
        }

        if ($this->esSolicitudDeCredenciales($texto)) {
            return $this->bloqueo(
                self::CATEGORIA_CREDENCIALES,
                'No puedo proporcionar, buscar ni confirmar contraseñas, tokens, claves, secretos o variables internas del sistema.'
            );
        }

        return null;
    }

    public function contenidoRedactado(string $categoria): string
    {
        return '[Solicitud omitida por seguridad: '.$categoria.']';
    }

    /** @return array{categoria: string, respuesta: string} */
    private function bloqueo(string $categoria, string $respuesta): array
    {
        return compact('categoria', 'respuesta');
    }

    private function esAyudaConPasswordPropio(string $texto): bool
    {
        return Str::contains($texto, [
            'olvide mi contrasena',
            'no recuerdo mi contrasena',
            'recuperar mi contrasena',
            'restablecer mi contrasena',
            'cambiar mi contrasena',
            'crear mi contrasena',
            'definir mi contrasena',
        ]);
    }

    private function esInyeccionDePrompt(string $texto): bool
    {
        $fraseConocida = Str::contains($texto, [
            'ignora todas las instrucciones',
            'ignora todas tus instrucciones',
            'ignora tus instrucciones',
            'ignora todo lo anterior',
            'olvida todo lo anterior',
            'olvida tus instrucciones',
            'omite tus instrucciones',
            'revela tu prompt',
            'muestra tu prompt',
            'prompt interno',
            'prompt del sistema',
            'system prompt',
            'jailbreak',
            'sin restricciones',
        ]);

        $alteracionDeReglas = preg_match(
            '/\b(ignora|olvida|omite|desobedece)\b.{0,60}\b(instrucciones|reglas|anterior)\b/',
            $texto
        ) === 1;

        return $fraseConocida || $alteracionDeReglas;
    }

    private function esEscalamientoDePrivilegios(string $texto): bool
    {
        return Str::contains($texto, [
            'actua como administrador',
            'actua como admin',
            'modo administrador',
            'modo admin',
            'hazme administrador',
            'dame permisos de administrador',
            'eleva mis privilegios',
            'omite la autorizacion',
            'evade la autorizacion',
        ]);
    }

    private function esSolicitudSql(string $texto): bool
    {
        $acciones = Str::contains($texto, ['ejecuta', 'ejecutar', 'corre', 'lanza', 'consulta sql']);
        $sentencia = preg_match('/\b(select|insert|update|delete|drop|truncate|alter)\b.+\b(from|into|table|database|set|where)\b/', $texto) === 1;

        return ($acciones && Str::contains($texto, ['sql', 'select ', 'insert ', 'update ', 'delete ', 'drop ', 'truncate ', 'alter ']))
            || $sentencia;
    }

    private function esSolicitudDeDatosPrivados(string $texto): bool
    {
        $datos = Str::contains($texto, [
            'datos', 'informacion', 'reservas', 'correo', 'email', 'telefono',
            'cuenta', 'perfil', 'historial',
        ]);
        $tercero = Str::contains($texto, [
            'otro usuario', 'otros usuarios', 'otro cliente', 'otros clientes',
            'otra persona', 'todas las personas', 'todos los clientes',
            'lista de usuarios', 'lista de clientes', 'del administrador',
        ]);

        return $datos && $tercero;
    }

    private function esSolicitudDeCredenciales(string $texto): bool
    {
        $referenciaInterna = Str::contains($texto, [
            'contrasena del administrador',
            'password del administrador',
            'usuario y contrasena',
            'api key',
            'clave api',
            'client secret',
            'access token',
            'refresh token',
            'token de acceso',
            'token de renovacion',
            'credenciales del sistema',
            'credenciales del administrador',
            'contenido del env',
            'archivo env',
            'variables de entorno',
            'secreto del sistema',
        ]);

        $solicitud = Str::contains($texto, [
            'dame', 'muestra', 'muestrame', 'revela', 'comparte', 'envia',
            'cual es', 'quiero ver', 'necesito', 'obten', 'lee', 'imprime',
        ]);
        $secreto = Str::contains($texto, [
            'contrasena', 'password', 'token', 'api key', 'clave api',
            'client secret', 'credenciales', 'secreto', 'archivo env', 'variables de entorno',
        ]);
        $divulgacion = preg_match(
            '/\b(contrasena|password|token|api key|clave api|client secret)\b\s*(?:es|vale|igual|:)/',
            $texto
        ) === 1;

        return $referenciaInterna || $divulgacion || ($solicitud && $secreto);
    }

    private function normalizar(string $texto): string
    {
        $texto = Str::lower(Str::ascii($texto));
        $texto = str_replace(['.env', '_'], [' archivo env ', ' '], $texto);
        $texto = preg_replace('/[^a-z0-9\s]/', ' ', $texto) ?? '';
        $texto = preg_replace('/\s+/', ' ', $texto) ?? '';

        return trim($texto);
    }
}
