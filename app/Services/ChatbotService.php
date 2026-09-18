<?php

namespace App\Services;

use App\Models\Balon;
use App\Models\Cancha;
use App\Models\ChatbotEjecucion;
use App\Models\Conversacion;
use App\Models\Horario;
use App\Models\MensajeChatbot;
use App\Models\Reserva;
use App\Models\User;
use App\Services\Chatbot\AnalisisNlp;
use App\Services\Chatbot\ChatbotTraceService;
use App\Services\Chatbot\DetectorIntencion;
use App\Services\Chatbot\ExtractorEntidades;
use App\Services\Chatbot\PoliticaSeguridadChatbot;
use App\Services\Chatbot\ProveedorExterno;
use App\Services\Chatbot\ReservaConversacionalService;
use App\Services\Chatbot\ResultadoProveedorNlp;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ChatbotService
{
    public const NOMBRE_BOT = 'WallyBot';

    public function __construct(
        private readonly DetectorIntencion $detector,
        private readonly ExtractorEntidades $extractor,
        private readonly PoliticaSeguridadChatbot $politicaSeguridad,
        private readonly ProveedorExterno $proveedorExterno,
        private readonly ChatbotTraceService $traceService,
        private readonly ReservaConversacionalService $reservaConversacional,
        private readonly ReservaService $reservaService,
    ) {}

    /**
     * Procesa el mensaje del usuario dentro de una conversación y
     * devuelve la respuesta del bot (texto + intención detectada).
     *
     * @return array{respuesta: string, intencion: string, componentes: array<int, array<string, mixed>>}
     */
    public function responder(string $mensaje, Conversacion $conversacion, ?User $usuario = null): array
    {
        $mensaje = trim($mensaje);
        if ($mensaje === '') {
            return ['respuesta' => $this->ayuda(), 'intencion' => DetectorIntencion::NO_DETECTADA];
        }

        $bloqueo = $this->politicaSeguridad->evaluar($mensaje);
        if ($bloqueo) {
            return $this->respuestaBloqueada($conversacion, $bloqueo);
        }

        // El proveedor externo (si está configurado y responde) tiene prioridad;
        // si no hay proveedor o falla, se usa el motor local por reglas.
        $analisisLocal = $this->analisisLocal($mensaje);
        $tieneBorrador = $this->reservaConversacional->tieneBorrador($conversacion);
        $resultadoProveedor = $tieneBorrador
            ? ResultadoProveedorNlp::noIntentado('draft_active')
            : $this->proveedorExterno->analizar($mensaje);
        $analisisExterno = $resultadoProveedor->analysis;
        $intencionLocal = $analisisLocal->intencion;
        $intencion = $analisisExterno?->intencion ?? $intencionLocal;

        // El proveedor solo propone entidades; cada referencia se resuelve en la BD.
        $referenciaCancha = $analisisExterno?->canchaReferencia;
        $cancha = $this->extractor->cancha($mensaje)
            ?? ($referenciaCancha ? $this->extractor->cancha($referenciaCancha) : null);
        $fecha = $analisisExterno?->fecha ?? $analisisLocal->fecha;
        $hora = $analisisExterno?->horaInicio ?? $analisisLocal->horaInicio;
        $horaFin = $analisisExterno?->horaFin ?? $analisisLocal->horaFin;
        $duracion = $analisisExterno?->duracionMinutos ?? $analisisLocal->duracionMinutos;

        if ($this->reservaConversacional->tieneBorrador($conversacion)
            && $intencionLocal === DetectorIntencion::NO_DETECTADA) {
            // Una confirmación, una negativa o el nombre de un balón pueden no
            // pertenecer al catálogo general de intenciones. El borrador tiene
            // prioridad sobre una clasificación externa para mantener el flujo.
            $intencion = DetectorIntencion::HACER_RESERVA;
        } elseif ($intencion === DetectorIntencion::NO_DETECTADA) {
            $intencion = $this->inferirIntencionPorContexto($conversacion, $cancha, $fecha, $hora);
        }

        $nuevoContexto = [];
        if ($cancha) {
            $nuevoContexto['cancha_id'] = $cancha->id;
        }
        if ($fecha) {
            $nuevoContexto['fecha'] = $fecha;
        }
        if ($hora) {
            $nuevoContexto['hora'] = $hora;
        }
        if ($horaFin) {
            $nuevoContexto['hora_fin'] = $horaFin;
        }
        if ($duracion) {
            $nuevoContexto['duracion_minutos'] = $duracion;
        }
        $reservaId = $analisisExterno?->reservaId ?? $analisisLocal->reservaId;
        if ($reservaId) {
            $nuevoContexto['reserva_id'] = $reservaId;
        }
        if (in_array($intencion, [
            DetectorIntencion::CONSULTAR_HORARIOS,
            DetectorIntencion::CONSULTAR_PRECIOS,
            DetectorIntencion::CONSULTAR_DISPONIBILIDAD,
            DetectorIntencion::HACER_RESERVA,
        ], true)) {
            $nuevoContexto['intencion_pendiente'] = $intencion;
        }
        if ($nuevoContexto) {
            $conversacion->actualizarContexto($nuevoContexto);
        }

        // Las operaciones que pueden escribir en la base de datos se resuelven
        // siempre mediante código determinista y servicios de negocio. El
        // proveedor generativo nunca puede confirmar una reserva por sí solo.
        if (! $horaFin && $hora && $duracion) {
            $horaFin = Carbon::parse($hora)->addMinutes($duracion)->format('H:i:s');
        }

        $respuesta = $intencion === DetectorIntencion::HACER_RESERVA
            ? $this->reservaConversacional->procesar(
                $mensaje,
                $conversacion,
                $usuario,
                $cancha,
                $fecha,
                $hora,
                $horaFin,
            )
            : $this->respuestaLocal($intencion, $conversacion, $usuario, $hora);

        // Guardar ambos mensajes en la conversación
        $mensajeUsuario = $conversacion->mensajes()->create([
            'emisor' => MensajeChatbot::EMISOR_USUARIO,
            'contenido' => $mensaje,
            'intencion' => $intencion,
        ]);
        $conversacion->mensajes()->create([
            'emisor' => MensajeChatbot::EMISOR_BOT,
            'contenido' => $respuesta,
            'intencion' => $intencion,
        ]);

        $motor = $tieneBorrador
            ? ChatbotEjecucion::MOTOR_DRAFT
            : ($analisisExterno
                ? ChatbotEjecucion::MOTOR_EXTERNAL
                : ChatbotEjecucion::MOTOR_LOCAL);
        $analisisUtilizado = $analisisExterno ?? $analisisLocal;
        $parametrosTraza = array_merge($analisisUtilizado->toArray(), [
            'cancha_id' => $cancha?->id,
        ]);
        $this->registrarTrazaSegura(
            $mensajeUsuario,
            $resultadoProveedor,
            $motor,
            $intencion,
            $parametrosTraza
        );

        return [
            'respuesta' => $respuesta,
            'intencion' => $intencion,
            'componentes' => $this->componentes($conversacion->fresh(), $intencion, $respuesta),
        ];
    }

    /**
     * @param  array{categoria: string, respuesta: string}  $bloqueo
     * @return array{respuesta: string, intencion: string}
     */
    private function respuestaBloqueada(Conversacion $conversacion, array $bloqueo): array
    {
        $intencion = DetectorIntencion::SOLICITUD_BLOQUEADA;

        $mensajeUsuario = $conversacion->mensajes()->create([
            'emisor' => MensajeChatbot::EMISOR_USUARIO,
            'contenido' => $this->politicaSeguridad->contenidoRedactado($bloqueo['categoria']),
            'intencion' => $intencion,
        ]);
        $conversacion->mensajes()->create([
            'emisor' => MensajeChatbot::EMISOR_BOT,
            'contenido' => $bloqueo['respuesta'],
            'intencion' => $intencion,
        ]);

        $this->registrarTrazaSegura(
            $mensajeUsuario,
            ResultadoProveedorNlp::noIntentado('blocked_before_provider'),
            ChatbotEjecucion::MOTOR_BLOCKED,
            $intencion
        );

        return ['respuesta' => $bloqueo['respuesta'], 'intencion' => $intencion];
    }

    /**
     * La telemetría es best effort: nunca debe alterar la respuesta funcional.
     *
     * @param  array<string, mixed>  $parametros
     */
    private function registrarTrazaSegura(
        MensajeChatbot $mensaje,
        ResultadoProveedorNlp $resultado,
        string $motor,
        ?string $intencion,
        array $parametros = [],
    ): void {
        try {
            $this->traceService->registrar($mensaje, $resultado, $motor, $intencion, $parametros);
        } catch (Throwable $exception) {
            Log::warning('Chatbot observabilidad: el servicio de trazas falló sin interrumpir la respuesta.', [
                'conversation_id' => $mensaje->conversacion_id,
                'message_id' => $mensaje->id,
                'exception' => $exception::class,
            ]);
        }
    }

    private function analisisLocal(string $mensaje): AnalisisNlp
    {
        $rango = $this->extractor->rangoHoras($mensaje);

        return new AnalisisNlp(
            intencion: $this->detector->detectar($mensaje),
            reservaId: $this->extractor->reservaId($mensaje),
            fecha: $this->extractor->fecha($mensaje),
            horaInicio: $rango['inicio'] ?? $this->extractor->hora($mensaje),
            horaFin: $rango['fin'] ?? null,
            duracionMinutos: $this->extractor->duracionMinutos($mensaje),
        );
    }

    /**
     * Respuesta del motor local por reglas, según la intención detectada.
     */
    private function respuestaLocal(
        string $intencion,
        Conversacion $conversacion,
        ?User $usuario,
        ?string $horaActual = null,
    ): string {
        return match ($intencion) {
            DetectorIntencion::SALUDO => $this->saludo($conversacion),
            DetectorIntencion::DESPEDIDA => $this->despedida(),
            DetectorIntencion::AYUDA => $this->ayuda(),
            DetectorIntencion::CONTACTO => $this->contacto(),
            DetectorIntencion::CONSULTAR_HORARIOS => $this->horarios($conversacion),
            DetectorIntencion::CONSULTAR_PRECIOS => $this->precios($conversacion),
            DetectorIntencion::CONSULTAR_DISPONIBILIDAD => $this->disponibilidad($conversacion, $horaActual),
            DetectorIntencion::CONSULTAR_ESTADO_RESERVA => $this->estadoReservas($conversacion, $usuario),
            DetectorIntencion::MODIFICAR_RESERVA => 'Entendí que deseas modificar una reserva. La ejecución segura de ese cambio se habilitará en la fase de operaciones conversacionales.',
            DetectorIntencion::CANCELAR_RESERVA => 'Entendí que deseas cancelar una reserva. Mientras se habilita el flujo conversacional, puedes hacerlo desde Mis reservas.',
            default => $this->ayuda($conversacion),
        };
    }

    private function inferirIntencionPorContexto(
        Conversacion $conversacion,
        ?Cancha $cancha,
        ?string $fecha,
        ?string $hora,
    ): string {
        if (! $cancha && ! $fecha && ! $hora) {
            return DetectorIntencion::NO_DETECTADA;
        }

        $pendiente = $conversacion->contextoDe('intencion_pendiente');
        $continuables = [
            DetectorIntencion::CONSULTAR_HORARIOS,
            DetectorIntencion::CONSULTAR_PRECIOS,
            DetectorIntencion::CONSULTAR_DISPONIBILIDAD,
            DetectorIntencion::HACER_RESERVA,
        ];

        return in_array($pendiente, $continuables, true)
            ? $pendiente
            : DetectorIntencion::NO_DETECTADA;
    }

    /**
     * Saludo personalizado según el estado de la conversación.
     */
    private function saludo(Conversacion $conversacion): string
    {
        $nombre = $conversacion->usuario?->name;
        $parteNombre = $nombre ? ' '.Str::before($nombre, ' ') : '';

        return "¡Hola{$parteNombre}! 👋 Soy {$this->nombreBot()}, tu asistente de reservas.\n\n"
            .'Puedo ayudarte con: horarios, precios, disponibilidad, reservas y el estado de tus reservas. '
            .'Escribe "ayuda" para ver todo lo que sé hacer.';
    }

    private function despedida(): string
    {
        return '¡Hasta pronto! 👋 Espero verte en la cancha. Si necesitas algo más, aquí estaré.';
    }

    /**
     * Mensaje de ayuda con las capacidades del bot.
     */
    private function ayuda(?Conversacion $conversacion = null): string
    {
        $sugerencias = [
            '📋 *Horarios:* "¿qué horarios tiene la cancha 1?"',
            '💰 *Precios:* "¿cuánto cuesta la cancha 2?"',
            '🗓️ *Disponibilidad:* "¿hay horario libre mañana en la cancha 1?"',
            '📝 *Reservar:* "quiero reservar la cancha 1 el sábado"',
            '🔎 *Mis reservas:* "¿cuál es el estado de mi reserva?"',
            '📍 *Contacto:* "¿cuál es su teléfono y dirección?"',
        ];

        $texto = "Soy {$this->nombreBot()}, el asistente virtual de las canchas de Wally. 🤖\n\n"
            .'Puedo responder consultas reales usando la información del sistema. Algunos ejemplos:'
            ."\n\n".implode("\n", $sugerencias)
            ."\n\nTambién recuerdo el contexto: si mencionas una cancha o fecha, las uso para responder tus siguientes preguntas.";

        // Si hay una cancha en contexto, ofrecer ayuda específica
        if ($conversacion && $canchaId = $conversacion->contextoDe('cancha_id')) {
            $cancha = Cancha::find($canchaId);
            if ($cancha) {
                $texto .= "\n\nRecuerdo que estabas consultando sobre *{$cancha->nombre}*. "
                    .'Puedes preguntarme sus horarios, precios o disponibilidad.';
            }
        }

        return $texto;
    }

    private function contacto(): string
    {
        $telefono = config('chatbot.telefono');
        $direccion = config('chatbot.direccion');
        $datos = array_filter([
            $direccion ? "📍 *Dirección:* {$direccion}" : null,
            $telefono ? "📞 *Teléfono / WhatsApp:* {$telefono}" : null,
        ]);

        return $datos
            ? implode("\n", $datos)
            : 'Los datos de contacto todavía no están registrados en la configuración del sistema.';
    }

    /**
     * Consulta de horarios de una cancha (usa la cancha del contexto si no se menciona).
     */
    private function horarios(Conversacion $conversacion): string
    {
        $cancha = $this->canchaDeContexto($conversacion);

        if (! $cancha) {
            return $this->pedirCancha('consultar sus horarios');
        }

        $porDia = $cancha->horarios()
            ->where('disponible', true)
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get()
            ->groupBy('dia_semana');

        if ($porDia->isEmpty()) {
            return "La cancha *{$cancha->nombre}* no tiene horarios registrados por el momento.";
        }

        $lineas = $porDia->map(function (Collection $horarios, int $dia) {
            $franjas = $horarios->map(fn ($h) => $this->formatoHora($h->hora_inicio).' – '.$this->formatoHora($h->hora_fin))->join(', ');

            return '• '.$horarios->first()->nombreDia().': '.$franjas;
        });

        return "Horarios de *{$cancha->nombre}*: 🕒\n\n".$lineas->join("\n");
    }

    /**
     * Consulta de precios (todas las canchas o una en concreto).
     */
    private function precios(Conversacion $conversacion): string
    {
        $cancha = $this->canchaDeContexto($conversacion);

        if ($cancha) {
            return "El precio de *{$cancha->nombre}* es de *Bs. {$cancha->precio_por_hora}* por hora. 💰";
        }

        $canchas = Cancha::disponibles()->orderBy('nombre')->get();

        if ($canchas->isEmpty()) {
            return 'Actualmente no hay canchas disponibles para reservar.';
        }

        $lineas = $canchas->map(fn ($c) => '• '.$c->nombre.': Bs. '.number_format($c->precio_por_hora, 2).' / hora');

        $balones = Balon::query()->orderBy('tipo')->orderBy('marca')->orderBy('nombre')->get();
        $detalleBalones = $balones->isEmpty()
            ? ''
            : "\n\n*Balones registrados:*\n".$balones->map(
                fn (Balon $balon) => '• '.$balon->marcaModelo().' ('.$balon->tipoLabel().'): Bs. '
                    .number_format((float) $balon->precio_por_hora, 2).' / hora'
            )->join("\n");

        return "Estos son los precios por hora de nuestras canchas:\n\n".$lineas->join("\n")
            .$detalleBalones
            ."\n\nPuedes preguntarme por una cancha específica, por ejemplo: \"¿cuánto cuesta la cancha 2?\"";

    }

    /**
     * Consulta de disponibilidad por cancha y fecha.
     */
    private function disponibilidad(Conversacion $conversacion, ?string $hora = null): string
    {
        $cancha = $this->canchaDeContexto($conversacion);
        $fecha = $conversacion->contextoDe('fecha') ?? now()->toDateString();

        if (! $cancha) {
            return $this->pedirCancha('consultar la disponibilidad');
        }

        $libres = $cancha->horariosDisponiblesPara($fecha);

        $fechaLegible = Carbon::parse($fecha)->translatedFormat('l d \d\e F');

        if ($libres->isEmpty()) {
            return "Lo siento, no quedan horarios libres para *{$cancha->nombre}* el {$fechaLegible}. 😕\n\n"
                .'Puedes probar con otra fecha o consultar la disponibilidad en el sitio.';
        }

        $lineas = $libres->map(fn ($h) => '• '.$this->formatoHora($h->hora_inicio).' – '.$this->formatoHora($h->hora_fin));

        if ($hora) {
            $horarioSolicitado = $libres->firstWhere('hora_inicio', $hora);

            if ($horarioSolicitado) {
                return 'Sí, el horario *'.$this->formatoHora($horarioSolicitado->hora_inicio)
                    .' – '.$this->formatoHora($horarioSolicitado->hora_fin).'* está libre para *'
                    .$cancha->nombre.'* el '.$fechaLegible.'. ✅';
            }

            return 'El horario *'.$this->formatoHora($hora).'* no está libre para *'.$cancha->nombre
                .'* el '.$fechaLegible.'. Estos son los horarios disponibles:'
                .PHP_EOL.PHP_EOL.$lineas->join(PHP_EOL);
        }

        return "Horarios *libres* para *{$cancha->nombre}* el {$fechaLegible}: ✅\n\n"
            .$lineas->join("\n")
            ."\n\n¿Quieres reservar alguna? Dime la hora o escribe \"quiero reservar\".";
    }

    /**
     * Estado de las reservas del usuario autenticado.
     */
    private function estadoReservas(Conversacion $conversacion, ?User $usuario): string
    {
        if (! $usuario) {
            return 'Para consultar el estado de tus reservas necesito que inicies sesión. 🔐 '
                .'Puedes hacerlo desde el botón "Iniciar sesión" en la parte superior y luego preguntarme de nuevo.';
        }

        $reservas = $usuario->reservas()
            ->with(['cancha', 'horario'])
            ->activas()
            ->orderByDesc('fecha')
            ->limit(5)
            ->get();

        if ($reservas->isEmpty()) {
            return "No tienes reservas activas por el momento. 📝\n\n"
                .'Si quieres, puedo ayudarte a encontrar un horario libre: dime la cancha y la fecha.';
        }

        $lineas = $reservas->map(function (Reserva $r) {
            $estado = Reserva::ESTADOS[$r->estado] ?? $r->estado;
            $hora = $r->iniciaEn()->format('H:i').' – '.$r->finalizaEn()->format('H:i');

            return '• #'.$r->id.' · '.$r->cancha->nombre.' · '.$r->fecha->translatedFormat('d/m/Y').' · '.$hora.' · *'.$estado.'*';
        });

        return "Tus reservas activas son:\n\n".$lineas->join("\n")
            ."\n\nPuedes gestionarlas desde *Mis reservas* en tu menú de usuario.";
    }

    /**
     * Devuelve la cancha del contexto (si existe y está disponible).
     */
    private function canchaDeContexto(Conversacion $conversacion): ?Cancha
    {
        $id = $conversacion->contextoDe('cancha_id');

        return $id ? Cancha::disponibles()->find($id) : null;
    }

    private function pedirCancha(string $accion): string
    {
        $nombres = Cancha::disponibles()->orderBy('nombre')->limit(3)->get()->map(fn ($c) => $c->nombre)->join(', ');

        return "Para {$accion}, dime primero *qué cancha* te interesa. 🤔\n\n"
            .($nombres !== '' ? "Actualmente tenemos: {$nombres}." : '');
    }

    private function formatoHora(string $hora): string
    {
        return Str::substr($hora, 0, 5);
    }

    private function nombreBot(): string
    {
        return config('chatbot.nombre', self::NOMBRE_BOT);
    }

    /**
     * Construye controles visuales seguros a partir del estado validado del backend.
     *
     * @return array<int, array<string, mixed>>
     */
    private function componentes(Conversacion $conversacion, string $intencion, string $respuesta): array
    {
        $componentes = [];
        $borrador = $conversacion->contextoDe(ReservaConversacionalService::CONTEXTO_BORRADOR);
        $fecha = $conversacion->contextoDe('fecha') ?? now()->toDateString();
        $horaInicio = $conversacion->contextoDe('hora');
        $horaFin = $conversacion->contextoDe('hora_fin');

        if (is_array($borrador)) {
            $fecha = $borrador['fecha'] ?? $fecha;
            $horaInicio = $borrador['hora'] ?? $horaInicio;
            $horaFin = $borrador['hora_fin'] ?? $horaFin;

            if (empty($borrador['cancha_id'])) {
                $componentes[] = $this->componenteCanchas($fecha, $horaInicio, $horaFin, true);
            } elseif (! array_key_exists('balon_elegido', $borrador) && ! empty($borrador['horario_id'])) {
                $horario = Horario::find($borrador['horario_id']);
                if ($horario) {
                    $componentes[] = $this->componenteBalones(
                        (string) $fecha,
                        $horario,
                        (string) ($horaFin ?: $horario->hora_fin)
                    );
                }
            }

            if (($borrador['estado'] ?? null) === 'confirmacion') {
                $componentes[] = [
                    'tipo' => 'acciones',
                    'acciones' => [
                        ['etiqueta' => 'Sí, confirmar reserva', 'mensaje' => 'sí, confirmar', 'estilo' => 'primary'],
                        ['etiqueta' => 'No, cancelar', 'mensaje' => 'no', 'estilo' => 'secondary'],
                    ],
                ];
            }
        } elseif ($intencion === DetectorIntencion::CONSULTAR_DISPONIBILIDAD) {
            $componentes[] = $this->componenteCanchas($fecha, $horaInicio, $horaFin, false);
        }

        if (str_starts_with($respuesta, 'Reserva #')) {
            $componentes[] = [
                'tipo' => 'enlace',
                'etiqueta' => 'Ver mis reservas',
                'url' => route('reservas.mis'),
            ];
        }

        return array_values(array_filter($componentes, fn (array $componente) => ! empty($componente['items']) || $componente['tipo'] !== 'opciones'));
    }

    /**
     * @return array<string, mixed>
     */
    private function componenteCanchas(?string $fecha, ?string $horaInicio, ?string $horaFin, bool $paraReserva): array
    {
        $items = Cancha::disponibles()->orderBy('nombre')->get()
            ->filter(function (Cancha $cancha) use ($fecha, $horaInicio, $horaFin): bool {
                if (! $fecha || ! $horaInicio) {
                    return true;
                }

                return $this->canchaCubreRango($cancha, $fecha, $horaInicio, $horaFin);
            })
            ->map(fn (Cancha $cancha) => [
                'id' => $cancha->id,
                'titulo' => $cancha->nombre,
                'descripcion' => 'Bs. '.number_format((float) $cancha->precio_por_hora, 2).' por hora',
                'imagen' => $cancha->imagenUrl(),
                'estado' => 'Disponible',
                'mensaje' => ($paraReserva ? 'quiero reservar ' : 'disponibilidad de ').$cancha->nombre,
            ])->values()->all();

        return ['tipo' => 'opciones', 'entidad' => 'cancha', 'items' => $items];
    }

    /**
     * @return array<string, mixed>
     */
    private function componenteBalones(string $fecha, Horario $horario, string $horaFin): array
    {
        $ocupados = $this->reservaService->balonesOcupados($fecha, $horario, $horaFin);
        $items = Balon::query()
            ->whereNotIn('id', $ocupados)
            ->orderBy('tipo')->orderBy('marca')->orderBy('nombre')->get()
            ->map(fn (Balon $balon) => [
                'id' => $balon->id,
                'titulo' => $balon->marcaModelo(),
                'descripcion' => $balon->tipoLabel().' · Bs. '.number_format((float) $balon->precio_por_hora, 2).' por hora',
                'imagen' => $balon->imagenUrl(),
                'estado' => 'Disponible',
                'mensaje' => $balon->marcaModelo(),
            ])->values()->all();

        array_unshift($items, [
            'id' => null,
            'titulo' => 'Traigo mi propio balón',
            'descripcion' => 'Se aplica la tarifa de la cancha',
            'imagen' => null,
            'estado' => 'Disponible',
            'mensaje' => 'traigo mi balón',
        ]);

        return ['tipo' => 'opciones', 'entidad' => 'balon', 'items' => $items];
    }

    private function canchaCubreRango(Cancha $cancha, string $fecha, string $horaInicio, ?string $horaFin): bool
    {
        $libres = $cancha->horariosDisponiblesPara($fecha);
        if (! $horaFin) {
            return $libres->contains('hora_inicio', $horaInicio);
        }

        $siguiente = $horaInicio;
        foreach ($libres->where('hora_inicio', '>=', $horaInicio)->sortBy('hora_inicio') as $franja) {
            if ($franja->hora_inicio !== $siguiente || $franja->hora_fin > $horaFin) {
                return false;
            }
            $siguiente = $franja->hora_fin;
            if ($siguiente === $horaFin) {
                return true;
            }
        }

        return false;
    }
}
