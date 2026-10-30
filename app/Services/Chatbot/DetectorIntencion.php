<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Str;

class DetectorIntencion
{
    public const SALUDO = 'saludo';

    public const DESPEDIDA = 'despedida';

    public const AYUDA = 'ayuda';

    public const CONTACTO = 'contacto';

    public const CONSULTAR_HORARIOS = 'consultar_horarios';

    public const CONSULTAR_PRECIOS = 'consultar_precios';

    public const CONSULTAR_DISPONIBILIDAD = 'consultar_disponibilidad';

    public const HACER_RESERVA = 'hacer_reserva';

    public const CONSULTAR_ESTADO_RESERVA = 'consultar_estado_reserva';

    public const MODIFICAR_RESERVA = 'modificar_reserva';

    public const CANCELAR_RESERVA = 'cancelar_reserva';

    public const NO_DETECTADA = 'no_detectada';

    public const SOLICITUD_BLOQUEADA = 'solicitud_bloqueada';

    /**
     * Intenciones posibles (orden de prioridad de evaluación).
     *
     * @var array<string, list<string>>
     */
    protected const PATRONES = [
        self::DESPEDIDA => [
            'adios', 'chao', 'chau', 'hasta luego', 'nos vemos', 'hasta pronto',
            'que tengas buen dia', 'bye', 'me voy',
        ],
        self::CONTACTO => [
            'telefono', 'whatsapp', 'celular', 'contacto', 'ubicacion',
            'direccion', 'donde estan', 'donde queda', 'como llego', 'horario de atencion al publico',
        ],
        self::CANCELAR_RESERVA => [
            'cancelar mi reserva', 'cancela mi reserva', 'anular mi reserva',
            'quiero cancelar la reserva', 'dar de baja mi reserva',
        ],
        self::MODIFICAR_RESERVA => [
            'modificar mi reserva', 'cambiar mi reserva', 'reprogramar mi reserva',
            'mover mi reserva', 'cambiar la fecha de mi reserva', 'cambiar el horario de mi reserva',
        ],
        self::CONSULTAR_ESTADO_RESERVA => [
            'mi reserva', 'mis reservas', 'estado de mi reserva', 'reserva que tengo',
            'tengo reserva', 'mis proximas reservas', 'verificar mi reserva', 'consulta de reserva',
        ],
        self::HACER_RESERVA => [
            'reservar', 'quiero jugar', 'apartar', 'agendar', 'reservacion',
            'hacer una reserva', 'una reserva', 'me gustaria reservar', 'reservarme',
        ],
        self::CONSULTAR_DISPONIBILIDAD => [
            'disponible', 'disponibilidad', 'libre', 'ocupada', 'ocupado', 'hay lugar',
            'hay cancha', 'tienen cancha', 'tienen espacio', 'se puede jugar', 'esta libre', 'hora libre', 'alguna hora',
        ],
        self::CONSULTAR_HORARIOS => [
            'horarios', 'horario', 'a que hora', 'que horas', 'abren', 'abre',
            'cierran', 'cierra', 'atienden', 'turno', 'atencion',
        ],
        self::CONSULTAR_PRECIOS => [
            'precio', 'precios', 'cuanto cuesta', 'cuanto vale', 'tarifa', 'tarifas',
            'costo', 'cuanto cobran', 'cuanto pagan', 'bs', 'bolivianos', 'alquiler',
        ],
        self::SALUDO => [
            'hola', 'buenos dias', 'buenas tardes', 'buenas noches', 'buen dia',
            'que tal', 'hey', 'saludos', 'hello',
        ],
        self::AYUDA => [
            'ayuda', 'que puedes hacer', 'como funciona', 'opciones', 'funciones',
            'que sabes', 'necesito ayuda', 'ayudame', 'menu',
        ],
    ];

    /**
     * Detecta la intención del mensaje usando reglas en español.
     * Normaliza acentos y mayúsculas antes de comparar.
     */
    public function detectar(string $mensaje): string
    {
        $texto = $this->normalizar($mensaje);

        if ($texto === '') {
            return self::NO_DETECTADA;
        }

        if (in_array(trim($texto), ['cancha', 'canchas'], true)) {
            return self::CONSULTAR_DISPONIBILIDAD;
        }

        foreach (self::PATRONES as $intencion => $patrones) {
            foreach ($patrones as $patron) {
                if (Str::contains($texto, $patron)) {
                    return $intencion;
                }
            }
        }

        return self::NO_DETECTADA;
    }

    /** @return list<string> */
    public static function catalogo(): array
    {
        return [
            self::SALUDO,
            self::DESPEDIDA,
            self::AYUDA,
            self::CONTACTO,
            self::CONSULTAR_HORARIOS,
            self::CONSULTAR_PRECIOS,
            self::CONSULTAR_DISPONIBILIDAD,
            self::HACER_RESERVA,
            self::CONSULTAR_ESTADO_RESERVA,
            self::MODIFICAR_RESERVA,
            self::CANCELAR_RESERVA,
            self::NO_DETECTADA,
        ];
    }

    /**
     * Normaliza el texto: minúsculas, sin tildes ni signos de puntuación.
     */
    public function normalizar(string $texto): string
    {
        $texto = Str::lower(Str::ascii($texto));

        return preg_replace('/[^a-z0-9\s]/u', ' ', $texto) ?? '';
    }
}
