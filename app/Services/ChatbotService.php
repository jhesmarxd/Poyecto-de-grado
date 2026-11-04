<?php

namespace App\Services;

use App\Models\Balon;
use App\Models\Cancha;
use App\Models\ChatbotEjecucion;
use App\Models\Conversacion;
use App\Models\Horario;
use App\Models\HorarioSemanal;
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
use App\Services\Reservas\ResultadoDisponibilidad;
use Carbon\Carbon;
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
        private readonly PoliticaReservasService $politicaReservas,
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

        $textoNormalizado = $this->detector->normalizar($mensaje);
        if (Str::contains($textoNormalizado, ['olvida todo', 'empecemos de nuevo', 'empezar de nuevo', 'reinicia'])) {
            $this->reservaConversacional->reiniciar($conversacion);

            return $this->respuestaControlada(
                $mensaje,
                'Listo, olvidé los datos temporales. Empecemos de nuevo: ¿qué fecha y horario deseas consultar?',
                DetectorIntencion::AYUDA,
                $conversacion,
            );
        }

        if (Str::contains($textoNormalizado, ['ultima hora disponible', 'ultimo horario disponible'])) {
            $fecha = $this->extractor->fecha($mensaje)
                ?? now($this->politicaReservas->zonaHoraria())->toDateString();
            $ultima = $this->ultimaDisponibilidad($conversacion, $fecha);

            return $this->respuestaControlada(
                $mensaje,
                $ultima['respuesta'],
                DetectorIntencion::CONSULTAR_DISPONIBILIDAD,
                $conversacion,
                $ultima['estado'],
            );
        }

        $ambiguedadPendiente = $conversacion->contextoDe('ambiguedad_horaria');
        $resolucionAmbiguedad = null;
        if (is_array($ambiguedadPendiente) && preg_match('/\b(am|pm|mañana|tarde|noche)\b/ui', $mensaje, $m)) {
            $meridiano = in_array(mb_strtolower($m[1]), ['am', 'mañana'], true) ? 'am' : 'pm';
            $resolucionAmbiguedad = $this->extractor->resolverHorarioAmbiguo($ambiguedadPendiente, $meridiano);
            $contexto = $conversacion->contexto ?? [];
            unset($contexto['ambiguedad_horaria'], $contexto['dato_pendiente']);
            $conversacion->update(['contexto' => $contexto]);
        } elseif (! is_array($ambiguedadPendiente) && ($ambigua = $this->extractor->horarioAmbiguo($mensaje))) {
            $intencionAmbigua = $this->detector->detectar($mensaje);
            if ($intencionAmbigua === DetectorIntencion::NO_DETECTADA) {
                $intencionAmbigua = Str::contains($textoNormalizado, ['reserv'])
                    ? DetectorIntencion::HACER_RESERVA
                    : DetectorIntencion::CONSULTAR_DISPONIBILIDAD;
            }
            $fechaAmbigua = $this->extractor->fecha($mensaje) ?? now($this->politicaReservas->zonaHoraria())->toDateString();
            $canchaAmbigua = $this->extractor->cancha($mensaje);
            $conversacion->actualizarContexto(array_filter([
                'ambiguedad_horaria' => $ambigua,
                'intencion_pendiente' => $intencionAmbigua,
                'fecha' => $fechaAmbigua,
                'cancha_id' => $canchaAmbigua?->id,
                'dato_pendiente' => 'am_pm',
            ], fn ($valor) => $valor !== null));
            $fin = $ambigua['fin_hora'];
            $pregunta = $fin !== null
                ? '¿Te refieres de '.sprintf('%d:%02d', $ambigua['inicio_hora'], $ambigua['inicio_minuto'])
                    .' a '.sprintf('%d:%02d', $fin, $ambigua['fin_minuto']).' AM o PM?'
                : '¿Te refieres a las '.sprintf('%d:%02d', $ambigua['inicio_hora'], $ambigua['inicio_minuto']).' AM o PM?';

            return $this->respuestaControlada($mensaje, $pregunta, $intencionAmbigua, $conversacion, 'horario_ambiguo');
        }

        if (! $resolucionAmbiguedad && ($seguimiento = $this->resolverSeguimientoDisponibilidad($mensaje, $conversacion))) {
            return $this->respuestaControlada(
                $mensaje,
                $seguimiento['respuesta'],
                $seguimiento['intencion'] ?? DetectorIntencion::CONSULTAR_DISPONIBILIDAD,
                $conversacion,
                $seguimiento['estado'] ?? null,
            );
        }

        // El proveedor externo (si está configurado y responde) tiene prioridad;
        // si no hay proveedor o falla, se usa el motor local por reglas.
        $analisisLocal = $this->analisisLocal($mensaje);
        $tieneBorrador = $this->reservaConversacional->tieneBorrador($conversacion);
        $resultadoProveedor = ($tieneBorrador || $resolucionAmbiguedad)
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
        $hora = $resolucionAmbiguedad['inicio'] ?? $analisisExterno?->horaInicio ?? $analisisLocal->horaInicio;
        $horaFin = $resolucionAmbiguedad['fin'] ?? $analisisExterno?->horaFin ?? $analisisLocal->horaFin;
        $duracion = $analisisExterno?->duracionMinutos
            ?? $analisisLocal->duracionMinutos
            ?? $conversacion->contextoDe('duracion_minutos');

        if (Str::contains($textoNormalizado, ['media hora despues', '30 minutos despues'])
            && $conversacion->contextoDe('hora')) {
            $inicioAnterior = $conversacion->contextoDe('hora');
            $finAnterior = $conversacion->contextoDe('hora_fin');
            $duracionAnterior = $finAnterior
                ? Carbon::parse($inicioAnterior)->diffInMinutes(Carbon::parse($finAnterior))
                : 60;
            $hora = Carbon::parse($inicioAnterior)->addMinutes(30)->format('H:i:s');
            $horaFin = Carbon::parse($hora)->addMinutes($duracionAnterior)->format('H:i:s');
            $duracion = $duracionAnterior;
        }

        if ($fecha && $hora && $this->extractor->mencionaDiaSemana($mensaje)) {
            $inicioPedido = Carbon::parse($fecha.' '.$hora, $this->politicaReservas->zonaHoraria());
            if ($inicioPedido->lte(Carbon::now($this->politicaReservas->zonaHoraria()))) {
                $fecha = Carbon::parse($fecha, $this->politicaReservas->zonaHoraria())->addWeek()->toDateString();
            }
        }

        if ($resolucionAmbiguedad) {
            $intencion = $conversacion->contextoDe('intencion_pendiente', DetectorIntencion::CONSULTAR_DISPONIBILIDAD);
        } elseif ($this->reservaConversacional->tieneBorrador($conversacion)) {
            // Si el usuario pregunta por horarios, precios, contacto o ayuda mientras está en borrador, respondemos su consulta
            if (in_array($intencion, [
                DetectorIntencion::CONSULTAR_HORARIOS,
                DetectorIntencion::CONSULTAR_PRECIOS,
                DetectorIntencion::CONTACTO,
                DetectorIntencion::AYUDA,
            ], true)) {
                // Se mantiene la intención informativa
            } elseif ($cancha || $fecha || $hora || $duracion || $intencion === DetectorIntencion::HACER_RESERVA) {
                $intencion = DetectorIntencion::HACER_RESERVA;
            } elseif ($intencionLocal === DetectorIntencion::NO_DETECTADA) {
                $intencion = DetectorIntencion::HACER_RESERVA;
            }
        } elseif ($intencion === DetectorIntencion::NO_DETECTADA) {
            $intencion = $this->inferirIntencionPorContexto($conversacion, $cancha, $fecha, $hora);
        }

        if (! $fecha && in_array($intencion, [DetectorIntencion::CONSULTAR_DISPONIBILIDAD, DetectorIntencion::HACER_RESERVA], true)) {
            $fecha = $conversacion->contextoDe('fecha')
                ?? now($this->politicaReservas->zonaHoraria())->toDateString();
        }

        if ($fecha && Str::contains($textoNormalizado, ['cuando abran', 'cuando abre'])) {
            $semanal = $this->politicaReservas->horarioSemanal($fecha);
            if ($semanal?->abierto) {
                $hora = $semanal->hora_apertura;
            }
        }
        if ($fecha && Str::contains($textoNormalizado, ['hasta que cierren', 'hasta el cierre'])) {
            $semanal = $this->politicaReservas->horarioSemanal($fecha);
            if ($semanal?->abierto) {
                $horaFin = $semanal->hora_cierre;
            }
        }

        if ($intencion === DetectorIntencion::DESPEDIDA) {
            $this->reservaConversacional->reiniciar($conversacion);
        }

        $horaNuevaExplicita = $resolucionAmbiguedad
            || $analisisExterno?->horaInicio
            || $analisisLocal->horaInicio;
        if ($horaNuevaExplicita && ! $horaFin && ! $duracion) {
            $contexto = $conversacion->contexto ?? [];
            unset($contexto['hora_fin'], $contexto['duracion_minutos']);
            $conversacion->update(['contexto' => $contexto]);
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
        $fechaAnterior = $conversacion->contextoDe('fecha');
        $cambioDeFecha = $fecha && $fechaAnterior && $fecha !== $fechaAnterior;
        $esNuevaSolicitudReserva = Str::contains($textoNormalizado, [
            'hacer una reserva', 'hacer reserva', 'otra reserva', 'nueva reserva',
            'queria hacer una reserva', 'quisiera hacer una reserva', 'quiero una reserva',
            'quiero hacer una reserva', 'deseo reservar',
        ]);

        if ($cambioDeFecha || ($esNuevaSolicitudReserva && ! $this->reservaConversacional->tieneBorrador($conversacion))) {
            $contexto = $conversacion->contexto ?? [];
            unset($contexto['hora'], $contexto['hora_fin'], $contexto['duracion_minutos'], $contexto['resultado_disponibilidad'], $contexto['alternativa_sugerida']);
            $conversacion->update(['contexto' => $contexto]);
            if (! $analisisExterno?->horaInicio && ! $analisisLocal->horaInicio) {
                $hora = null;
                $horaFin = null;
            }
            if (! $analisisExterno?->duracionMinutos && ! $analisisLocal->duracionMinutos) {
                $duracion = null;
            }
        }

        if (! $hora && $duracion && $conversacion->contextoDe('hora') && ! $cambioDeFecha && ! $esNuevaSolicitudReserva) {
            $hora = $conversacion->contextoDe('hora');
        }
        if (! $horaFin && $hora && $duracion) {
            $horaFin = Carbon::parse($hora)->addMinutes($duracion)->format('H:i:s');
            $conversacion->actualizarContexto(['hora' => $hora, 'hora_fin' => $horaFin, 'duracion_minutos' => $duracion]);
        }
        if (in_array($intencion, [DetectorIntencion::CONSULTAR_DISPONIBILIDAD, DetectorIntencion::HACER_RESERVA], true)) {
            if (! $cambioDeFecha && ! $esNuevaSolicitudReserva) {
                $hora ??= $conversacion->contextoDe('hora');
                $horaFin ??= $conversacion->contextoDe('hora_fin');
            }
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
            : $this->respuestaLocal($intencion, $conversacion, $usuario, $hora, $horaFin);

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

        $conversacionActual = $conversacion->fresh();

        return [
            'respuesta' => $respuesta,
            'intencion' => $intencion,
            'componentes' => $this->componentes($conversacionActual, $intencion, $respuesta),
            'resultado' => $conversacionActual->contextoDe('resultado_disponibilidad'),
            'dato_pendiente' => $conversacionActual->contextoDe('dato_pendiente'),
        ];
    }

    private function respuestaControlada(
        string $mensaje,
        string $respuesta,
        string $intencion,
        Conversacion $conversacion,
        ?string $estado = null,
    ): array {
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
        $this->registrarTrazaSegura(
            $mensajeUsuario,
            ResultadoProveedorNlp::noIntentado($estado ?? 'controlled_flow'),
            ChatbotEjecucion::MOTOR_DRAFT,
            $intencion,
        );

        $conversacionActual = $conversacion->fresh();

        return [
            'respuesta' => $respuesta,
            'intencion' => $intencion,
            'estado' => $estado,
            'componentes' => $this->componentes($conversacionActual, $intencion, $respuesta),
            'resultado' => $conversacionActual->contextoDe('resultado_disponibilidad'),
            'dato_pendiente' => $conversacionActual->contextoDe('dato_pendiente'),
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

    /**
     * Resuelve respuestas cortas que dependen de la última consulta sin
     * obligar al usuario a repetir fecha, horario y cancha.
     *
     * @return array{respuesta:string,intencion?:string,estado?:string}|null
     */
    private function resolverSeguimientoDisponibilidad(string $mensaje, Conversacion $conversacion): ?array
    {
        if ($this->reservaConversacional->tieneBorrador($conversacion)) {
            return null;
        }

        $texto = $this->detector->normalizar($mensaje);
        $resultadoAnterior = $conversacion->contextoDe('resultado_disponibilidad');
        $fecha = $conversacion->contextoDe('fecha');
        $inicio = $conversacion->contextoDe('hora');
        $fin = $conversacion->contextoDe('hora_fin');

        if (Str::contains($texto, ['entonces nada', 'dejalo', 'para la proxima', 'mejor espero', 'luego veo', 'no gracias'])) {
            $this->reservaConversacional->reiniciar($conversacion);

            return [
                'respuesta' => 'De acuerdo. No se registró ninguna reserva. Cuando quieras revisar otra fecha u horario, aquí estaré.',
                'estado' => 'finalizado_sin_reserva',
            ];
        }

        if (Str::contains($texto, ['mejor otro dia', 'revisar otro dia'])) {
            $contexto = $conversacion->contexto ?? [];
            unset($contexto['fecha'], $contexto['resultado_disponibilidad']);
            $contexto['dato_pendiente'] = 'fecha';
            $conversacion->update(['contexto' => $contexto]);

            return ['respuesta' => 'Claro. ¿Para qué día quieres que revise?', 'estado' => 'datos_insuficientes'];
        }

        $canchaSugeridaId = $conversacion->contextoDe('cancha_sugerida_id');
        if ($canchaSugeridaId && in_array($texto, ['si', 'dale', 'esta bien', 'de acuerdo'], true)) {
            $cancha = Cancha::disponibles()->find($canchaSugeridaId);
            if ($cancha && $fecha && $inicio && $fin) {
                $conversacion->actualizarContexto(['cancha_id' => $cancha->id]);
                $this->quitarContexto($conversacion, ['cancha_sugerida_id']);
                $resultado = $this->politicaReservas->consultarRango($fecha, $inicio, $fin, $cancha);
                $this->guardarResultadoDisponibilidad($conversacion, $resultado);

                return [
                    'respuesta' => $resultado->disponible()
                        ? "Perfecto. {$cancha->nombre} continúa disponible de ".$this->formatoHora($inicio).' a '.$this->formatoHora($fin).'.'
                        : $resultado->mensaje,
                    'estado' => $resultado->estado,
                ];
            }
        }

        $alternativaSugerida = $conversacion->contextoDe('alternativa_sugerida');
        if (is_array($alternativaSugerida) && in_array($texto, ['si', 'dale', 'esta bien', 'de acuerdo'], true)) {
            return $this->aceptarAlternativa($conversacion, $alternativaSugerida, $fecha);
        }

        $duracionSolicitada = $this->extractor->duracionMinutos($mensaje);
        if ($duracionSolicitada && is_array($resultadoAnterior)) {
            $alternativa = collect($resultadoAnterior['alternativas'] ?? [])->first(function (array $opcion) use ($duracionSolicitada): bool {
                return (int) Carbon::parse($opcion['inicio'])->diffInMinutes(Carbon::parse($opcion['fin'])) === $duracionSolicitada;
            });
            if (is_array($alternativa)) {
                return $this->aceptarAlternativa($conversacion, $alternativa, $fecha);
            }
        }

        if (is_array($resultadoAnterior)
            && Str::contains($texto, ['cualquiera', 'me da igual', 'la que este libre', 'no tengo preferencia'])) {
            $ids = $resultadoAnterior['cancha_ids'] ?? [];
            $sugerida = Cancha::disponibles()->whereIn('id', $ids)->orderBy('nombre')->first();
            if ($sugerida) {
                $conversacion->actualizarContexto(['cancha_sugerida_id' => $sugerida->id]);

                return [
                    'respuesta' => "Ambas opciones son equivalentes para la reserva. ¿Quieres continuar con {$sugerida->nombre}?",
                    'estado' => 'confirmar_cancha',
                ];
            }
        }

        if (is_array($resultadoAnterior)
            && ($resultadoAnterior['estado'] ?? null) === ResultadoDisponibilidad::FUERA_DE_RANGO
            && Str::contains($texto, ['por que', 'porque'])) {
            return [
                'respuesta' => 'Las reservas solo están habilitadas dentro del período configurado por el complejo: '
                    .$this->politicaReservas->configuracion()->anticipacion_maxima_dias.' días de anticipación.',
                'estado' => ResultadoDisponibilidad::FUERA_DE_RANGO,
            ];
        }

        if ($fecha && Str::contains($texto, ['cuando puedo reservar', 'cuando se habilita'])) {
            $dias = (int) $this->politicaReservas->configuracion()->anticipacion_maxima_dias;
            $habilitadaDesde = Carbon::parse($fecha)->subDays($dias);

            return [
                'respuesta' => 'Esa fecha entrará en la ventana de reservas el '
                    .$habilitadaDesde->translatedFormat('d \d\e F \d\e Y').'.',
                'estado' => 'informativo',
            ];
        }

        if ($fecha && Str::contains($texto, ['exactamente cuando abran', 'cuando abran'])) {
            $semanal = $this->politicaReservas->horarioSemanal($fecha);
            if (! $semanal?->abierto) {
                return ['respuesta' => 'Ese día el complejo está cerrado.', 'estado' => ResultadoDisponibilidad::DIA_CERRADO];
            }
            $contexto = $conversacion->contexto ?? [];
            $contexto['hora'] = $semanal->hora_apertura;
            $contexto['dato_pendiente'] = 'hora_fin_o_duracion';
            unset($contexto['hora_fin']);
            $conversacion->update(['contexto' => $contexto]);

            return [
                'respuesta' => 'Perfecto. La hora inicial será '.$this->formatoHora($semanal->hora_apertura)
                    .'. ¿Hasta qué hora o por cuánto tiempo deseas jugar?',
                'estado' => ResultadoDisponibilidad::DATOS_INSUFICIENTES,
            ];
        }

        if ($fecha && Str::contains($texto, ['hasta que cierren', 'hasta el cierre'])) {
            $semanal = $this->politicaReservas->horarioSemanal($fecha);
            if (! $semanal?->abierto) {
                return ['respuesta' => 'Ese día el complejo está cerrado.', 'estado' => ResultadoDisponibilidad::DIA_CERRADO];
            }
            $conversacion->actualizarContexto(['hora_fin' => $semanal->hora_cierre, 'dato_pendiente' => 'hora_inicio']);

            return [
                'respuesta' => 'El cierre es a las '.$this->formatoHora($semanal->hora_cierre)
                    .'. ¿A qué hora deseas comenzar?',
                'estado' => ResultadoDisponibilidad::DATOS_INSUFICIENTES,
            ];
        }

        if (! $fecha || ! $inicio || ! $fin) {
            return null;
        }

        $cancha = $conversacion->contextoDe('cancha_id')
            ? Cancha::disponibles()->find($conversacion->contextoDe('cancha_id'))
            : null;

        if (Str::contains($texto, ['seguro', 'verifica de nuevo', 'revisa de nuevo'])) {
            $resultado = $this->politicaReservas->consultarRango($fecha, $inicio, $fin, $cancha);
            $this->guardarResultadoDisponibilidad($conversacion, $resultado);

            return [
                'respuesta' => $resultado->disponible()
                    ? 'Sí, acabo de verificar nuevamente la base de datos y continúa disponible.'
                    : 'Acabo de verificar nuevamente. '.$resultado->mensaje,
                'estado' => $resultado->estado,
            ];
        }

        if (Str::contains($texto, ['las dos', 'ambas'])) {
            $resultado = $this->politicaReservas->consultarRango($fecha, $inicio, $fin);
            $this->guardarResultadoDisponibilidad($conversacion, $resultado);

            return [
                'respuesta' => $resultado->canchas->count() > 1
                    ? 'Sí, ambas canchas están disponibles durante todo el horario solicitado.'
                    : ($resultado->canchas->count() === 1
                        ? 'En este momento solo '.$resultado->canchas->first()->nombre.' está disponible durante todo el horario.'
                        : $resultado->mensaje),
                'estado' => $resultado->estado,
            ];
        }

        if (Str::contains($texto, ['hasta que hora esta libre', 'hasta que hora sigue libre'])) {
            $canchaObjetivo = $cancha;
            if (! $canchaObjetivo && is_array($resultadoAnterior)) {
                $canchaObjetivo = Cancha::find(collect($resultadoAnterior['cancha_ids'] ?? [])->first());
            }
            if ($canchaObjetivo) {
                $cursor = $inicio;
                foreach ($this->politicaReservas->horariosLibres($canchaObjetivo, $fecha)->sortBy('hora_inicio') as $franja) {
                    if ($franja->hora_inicio < $inicio) {
                        continue;
                    }
                    if ($franja->hora_inicio !== $cursor) {
                        break;
                    }
                    $cursor = $franja->hora_fin;
                }
                if ($cursor !== $inicio) {
                    return [
                        'respuesta' => $canchaObjetivo->nombre.' está libre de forma continua hasta las '.$this->formatoHora($cursor).'.',
                        'estado' => 'informativo',
                    ];
                }
            }
        }

        if (Str::contains($texto, ['cual recomiendas', 'puedo elegir despues'])) {
            return [
                'respuesta' => Str::contains($texto, 'elegir despues')
                    ? 'Sí. Puedes elegir después; volveré a verificar la disponibilidad antes de confirmar.'
                    : 'Si ambas están disponibles, no inventaré diferencias: puedes elegir cualquiera de las dos.',
                'estado' => 'informativo',
            ];
        }

        if (Str::contains($texto, ['puedes guardarmela', 'puedes bloquearla'])) {
            return [
                'respuesta' => 'No puedo bloquear ese horario sin completar y confirmar una reserva. La disponibilidad puede cambiar hasta la confirmación.',
                'estado' => 'informativo',
            ];
        }

        if (Str::contains($texto, ['que hay despues', 'cuando se libera', 'cuando queda libre', 'hora esta libre primero'])) {
            return $this->respuestaAlternativa($conversacion, $fecha, $inicio, $fin, $cancha, 'despues');
        }
        if (Str::contains($texto, ['y antes', 'que hay antes'])) {
            return $this->respuestaAlternativa($conversacion, $fecha, $inicio, $fin, $cancha, 'antes');
        }
        if (Str::contains($texto, ['mas cercano', 'opcion mas cercana'])) {
            return $this->respuestaAlternativa($conversacion, $fecha, $inicio, $fin, $cancha, 'cercana');
        }

        if (Str::contains($texto, ['nada', 'que pena', 'no tengo otra hora'])) {
            return [
                'respuesta' => 'No para el horario completo que solicitaste. Si quieres, puedo revisar opciones cercanas.',
                'estado' => is_array($resultadoAnterior) ? ($resultadoAnterior['estado'] ?? 'sin_disponibilidad') : 'sin_disponibilidad',
            ];
        }

        return null;
    }

    /** @param array{inicio:string,fin:string,cancha_id:int,fecha?:string} $opcion */
    private function aceptarAlternativa(Conversacion $conversacion, array $opcion, ?string $fechaBase): array
    {
        $fecha = $opcion['fecha'] ?? $fechaBase;
        $cancha = Cancha::disponibles()->find($opcion['cancha_id']);
        if (! $fecha || ! $cancha) {
            return [
                'respuesta' => 'Esa alternativa ya no está disponible. Indícame otra fecha u horario para revisarlo.',
                'estado' => ResultadoDisponibilidad::SIN_DISPONIBILIDAD,
            ];
        }

        $resultado = $this->politicaReservas->consultarRango($fecha, $opcion['inicio'], $opcion['fin'], $cancha);
        if (! $resultado->disponible()) {
            $this->guardarResultadoDisponibilidad($conversacion, $resultado);

            return [
                'respuesta' => 'Volví a comprobar esa alternativa. '.$resultado->mensaje,
                'estado' => $resultado->estado,
            ];
        }

        $conversacion->actualizarContexto([
            'fecha' => $fecha,
            'hora' => $opcion['inicio'],
            'hora_fin' => $opcion['fin'],
            'cancha_id' => $cancha->id,
            'resultado_disponibilidad' => $resultado->toArray(),
        ]);
        $this->quitarContexto($conversacion, ['alternativa_sugerida', 'dato_pendiente']);

        return [
            'respuesta' => 'Perfecto. Verifiqué nuevamente y '.$cancha->nombre.' está disponible el '
                .Carbon::parse($fecha)->translatedFormat('l d \d\e F').' de '
                .$this->formatoHora($opcion['inicio']).' a '.$this->formatoHora($opcion['fin']).'.',
            'estado' => $resultado->estado,
        ];
    }

    /** @return array{respuesta:string,estado:string} */
    private function ultimaDisponibilidad(Conversacion $conversacion, string $fecha): array
    {
        if ($error = $this->politicaReservas->validarFecha($fecha)) {
            $this->guardarResultadoDisponibilidad($conversacion, $error);

            return ['respuesta' => $error->mensaje, 'estado' => $error->estado];
        }

        $duraciones = $this->politicaReservas->configuracion()->duraciones_permitidas ?: [60];
        $duracionMinima = min(array_map('intval', $duraciones));
        $opciones = [];
        foreach (Cancha::disponibles()->orderBy('nombre')->get() as $cancha) {
            foreach ($this->politicaReservas->horariosLibres($cancha, $fecha) as $franja) {
                $fin = Carbon::parse($franja->hora_inicio)->addMinutes($duracionMinima)->format('H:i:s');
                if ($this->politicaReservas->rangoLibre($cancha, $fecha, $franja->hora_inicio, $fin)) {
                    $opciones[] = [
                        'inicio' => $franja->hora_inicio,
                        'fin' => $fin,
                        'cancha' => $cancha,
                    ];
                }
            }
        }

        $opcion = collect($opciones)->sortByDesc('inicio')->first();
        if (! $opcion) {
            $resultado = new ResultadoDisponibilidad(
                ResultadoDisponibilidad::SIN_DISPONIBILIDAD,
                'No queda un bloque continuo disponible para esa fecha.',
                fecha: $fecha,
            );
            $this->guardarResultadoDisponibilidad($conversacion, $resultado);

            return ['respuesta' => $resultado->mensaje, 'estado' => $resultado->estado];
        }

        $resultado = $this->politicaReservas->consultarRango(
            $fecha,
            $opcion['inicio'],
            $opcion['fin'],
            $opcion['cancha'],
        );
        $conversacion->actualizarContexto([
            'fecha' => $fecha,
            'hora' => $opcion['inicio'],
            'hora_fin' => $opcion['fin'],
            'cancha_id' => $opcion['cancha']->id,
            'resultado_disponibilidad' => $resultado->toArray(),
        ]);

        return [
            'respuesta' => 'La última hora disponible es en '.$opcion['cancha']->nombre.' de '
                .$this->formatoHora($opcion['inicio']).' a '.$this->formatoHora($opcion['fin']).'.',
            'estado' => $resultado->estado,
        ];
    }

    private function respuestaAlternativa(
        Conversacion $conversacion,
        string $fecha,
        string $inicio,
        string $fin,
        ?Cancha $cancha,
        string $direccion,
    ): array {
        $opcion = $this->politicaReservas->buscarBloqueContinuo($fecha, $inicio, $fin, $cancha, $direccion);
        if (! $opcion) {
            return [
                'respuesta' => 'No encontré un bloque continuo alternativo dentro del período habilitado.',
                'estado' => 'sin_disponibilidad',
            ];
        }

        $canchaOpcion = Cancha::find($opcion['cancha_id']);
        $conversacion->actualizarContexto(['alternativa_sugerida' => $opcion]);

        return [
            'respuesta' => 'La opción '.($direccion === 'antes' ? 'anterior' : 'más próxima')
                .' es '.($canchaOpcion?->nombre ?? 'una cancha').' el '
                .Carbon::parse($opcion['fecha'])->translatedFormat('l d \d\e F').' de '
                .$this->formatoHora($opcion['inicio']).' a '.$this->formatoHora($opcion['fin']).'.',
            'estado' => 'alternativa',
        ];
    }

    private function guardarResultadoDisponibilidad(Conversacion $conversacion, ResultadoDisponibilidad $resultado): void
    {
        $conversacion->actualizarContexto(['resultado_disponibilidad' => $resultado->toArray()]);
    }

    /** @param list<string> $claves */
    private function quitarContexto(Conversacion $conversacion, array $claves): void
    {
        $contexto = $conversacion->contexto ?? [];
        foreach ($claves as $clave) {
            unset($contexto[$clave]);
        }
        $conversacion->update(['contexto' => $contexto]);
    }

    private function analisisLocal(string $mensaje): AnalisisNlp
    {
        $rango = $this->extractor->rangoHoras($mensaje);
        $horaFinAislada = $rango ? null : $this->extractor->horaFinAislada($mensaje);

        return new AnalisisNlp(
            intencion: $this->detector->detectar($mensaje),
            reservaId: $this->extractor->reservaId($mensaje),
            fecha: $this->extractor->fecha($mensaje),
            horaInicio: $rango['inicio'] ?? ($horaFinAislada ? null : $this->extractor->hora($mensaje)),
            horaFin: $rango['fin'] ?? $horaFinAislada,
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
        ?string $horaFinActual = null,
    ): string {
        return match ($intencion) {
            DetectorIntencion::SALUDO => $this->saludo($conversacion),
            DetectorIntencion::DESPEDIDA => $this->despedida(),
            DetectorIntencion::AYUDA => $this->ayuda(),
            DetectorIntencion::CONTACTO => $this->contacto(),
            DetectorIntencion::CONSULTAR_HORARIOS => $this->horarios($conversacion),
            DetectorIntencion::CONSULTAR_PRECIOS => $this->precios($conversacion),
            DetectorIntencion::CONSULTAR_DISPONIBILIDAD => $this->disponibilidad($conversacion, $horaActual, $horaFinActual),
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

        if (in_array($pendiente, $continuables, true)) {
            return $pendiente;
        }

        return ($cancha || $fecha || $hora)
            ? DetectorIntencion::CONSULTAR_DISPONIBILIDAD
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
        $semanales = HorarioSemanal::query()->orderBy('dia_semana')->get();
        if ($semanales->isEmpty()) {
            return 'Los horarios de atención todavía no están configurados.';
        }

        $fecha = $conversacion->contextoDe('fecha');
        if ($fecha) {
            $semanal = $this->politicaReservas->horarioSemanal($fecha);
            $fechaLegible = Carbon::parse($fecha)->translatedFormat('l d \d\e F');
            if (! $semanal?->abierto) {
                return "El {$fechaLegible} el complejo estará cerrado y no se aceptarán reservas.";
            }

            return "El {$fechaLegible} atendemos de ".$this->formatoHora($semanal->hora_apertura)
                .' a '.$this->formatoHora($semanal->hora_cierre)
                .'. Los bloqueos especiales de esa fecha se comprueban al consultar disponibilidad.';
        }

        $lineas = $semanales->map(function (HorarioSemanal $horario) {
            $dia = Horario::DIAS[$horario->dia_semana] ?? 'Día '.$horario->dia_semana;

            return $horario->abierto
                ? '• '.$dia.': '.$this->formatoHora($horario->hora_apertura).' – '.$this->formatoHora($horario->hora_cierre)
                : '• '.$dia.': cerrado';
        });
        $alcance = $cancha ? ' aplicables a *'.$cancha->nombre.'*' : ' del complejo';

        return 'Horarios de atención'.$alcance.": 🕒\n\n".$lineas->join("\n")
            ."\n\nLas excepciones y bloqueos por fecha se validan al consultar disponibilidad.";
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
    private function disponibilidad(Conversacion $conversacion, ?string $hora = null, ?string $horaFin = null): string
    {
        $cancha = $this->canchaDeContexto($conversacion);
        $fecha = $conversacion->contextoDe('fecha') ?? now()->toDateString();

        if ($hora && ! $horaFin) {
            $conversacion->actualizarContexto(['dato_pendiente' => 'hora_fin_o_duracion']);

            return '¿Hasta qué hora deseas jugar o cuánto tiempo quieres reservar?';
        }

        $fechaLegible = Carbon::parse($fecha)->translatedFormat('l d \d\e F');

        if ($hora && $horaFin) {
            $resultado = $this->politicaReservas->consultarRango($fecha, $hora, $horaFin, $cancha);
            $contexto = $conversacion->contexto ?? [];
            unset($contexto['dato_pendiente']);
            $contexto['resultado_disponibilidad'] = $resultado->toArray();
            $conversacion->update(['contexto' => $contexto]);
            if ($resultado->disponible()) {
                $nombres = $resultado->canchas->pluck('nombre')->join(' y ');

                return 'Sí. *'.$nombres.'* '.($resultado->canchas->count() > 1 ? 'están disponibles' : 'está disponible')
                    .' el '.$fechaLegible.' de '.$this->formatoHora($hora).' a '.$this->formatoHora($horaFin).'. ✅';
            }
            if ($resultado->alternativas) {
                $lineas = collect($resultado->alternativas)->map(function (array $opcion) {
                    $nombre = Cancha::find($opcion['cancha_id'])?->nombre ?? 'Cancha';

                    return '• '.$nombre.': '.$this->formatoHora($opcion['inicio']).' – '.$this->formatoHora($opcion['fin']);
                })->join("\n");

                return $resultado->mensaje." Opciones cercanas:\n\n".$lineas;
            }

            return $resultado->mensaje;
        }

        $canchas = $cancha ? collect([$cancha]) : Cancha::disponibles()->orderBy('nombre')->get();
        $conversacion->actualizarContexto(['dato_pendiente' => 'hora_inicio_y_fin']);
        $bloques = $canchas->map(function (Cancha $item) use ($fecha) {
            $libres = $this->politicaReservas->horariosLibres($item, $fecha);
            if ($libres->isEmpty()) {
                return '*'.$item->nombre.'*: sin horarios libres.';
            }

            return '*'.$item->nombre.'*: '.$libres->map(
                fn ($h) => $this->formatoHora($h->hora_inicio).'–'.$this->formatoHora($h->hora_fin)
            )->join(', ');
        });

        if ($cancha && $this->politicaReservas->horariosLibres($cancha, $fecha)->isEmpty()) {
            return "Lo siento, no quedan horarios libres para *{$cancha->nombre}* el {$fechaLegible}.";
        }

        return "Disponibilidad para el {$fechaLegible}:\n\n".$bloques->join("\n")
            ."\n\nIndícame la hora inicial y final que prefieres.";
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
            $fecha = $borrador['fecha'] ?? null;
            $horaInicio = $borrador['hora'] ?? $horaInicio;
            $horaFin = $borrador['hora_fin'] ?? $horaFin;

            if (empty($borrador['cancha_id'])) {
                $componentes[] = $this->componenteCanchas($fecha, $horaInicio, $horaFin, true);
            } elseif (empty($borrador['hora']) && ! empty($borrador['fecha'])) {
                $canchaHorario = Cancha::find($borrador['cancha_id']);
                if ($canchaHorario) {
                    $componentes[] = $this->componenteHorarios($canchaHorario, (string) $fecha);
                }
            } elseif (! empty($borrador['hora']) && empty($borrador['hora_fin'])) {
                $componentes[] = $this->componenteDuraciones();
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
                if (auth()->check() && ! auth()->user()->tieneCelularValido()) {
                    $componentes[] = ['tipo' => 'contacto'];
                } else {
                    $componentes[] = [
                    'tipo' => 'acciones',
                    'acciones' => [
                        ['etiqueta' => 'Sí, confirmar reserva', 'mensaje' => 'sí, confirmar', 'estilo' => 'primary'],
                        ['etiqueta' => 'No, cancelar', 'mensaje' => 'no', 'estilo' => 'secondary'],
                    ],
                    ];
                }
            }
        } elseif ($intencion === DetectorIntencion::CONSULTAR_DISPONIBILIDAD) {
            $componentes[] = $this->componenteCanchas($fecha, $horaInicio, $horaFin, false);
            if ($canchaId = $conversacion->contextoDe('cancha_id')) {
                $canchaConsulta = Cancha::find($canchaId);
                if ($canchaConsulta && $fecha) {
                    $componentes[] = $this->componenteHorarios($canchaConsulta, (string) $fecha);
                }
            }
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
            ->map(function (Cancha $cancha) use ($fecha, $horaInicio, $horaFin, $paraReserva): array {
                $disponible = ! $fecha || ($horaInicio
                    ? $this->canchaCubreRango($cancha, $fecha, $horaInicio, $horaFin)
                    : $this->politicaReservas->horariosLibres($cancha, $fecha)->isNotEmpty());

                return [
                'id' => $cancha->id,
                'titulo' => $cancha->nombre,
                'descripcion' => collect([$cancha->descripcion, $cancha->color_malla ? 'Malla '.$cancha->color_malla : null, $cancha->altura_malla ? 'Altura '.$cancha->altura_malla : null, $cancha->tipo_piso ? 'Piso '.$cancha->tipo_piso : null, $cancha->tipo_recinto, 'Bs. '.number_format((float) $cancha->precio_por_hora, 2).' por hora'])->filter()->implode(' · '),
                'imagen' => $cancha->imagenUrl(),
                'estado' => $disponible ? 'Disponible' : 'No disponible',
                'disabled' => ! $disponible,
                'mensaje' => ($paraReserva ? 'quiero reservar ' : 'disponibilidad de ').$cancha->nombre,
                ];
            })->values()->all();

        return ['tipo' => 'opciones', 'entidad' => 'cancha', 'items' => $items];
    }

    /**
     * @return array<string, mixed>
     */
    private function componenteBalones(string $fecha, Horario $horario, string $horaFin): array
    {
        $ocupados = $this->reservaService->balonesOcupados($fecha, $horario, $horaFin);
        $items = Balon::query()
            ->orderBy('tipo')->orderBy('marca')->orderBy('nombre')->get()
            ->map(fn (Balon $balon) => [
                'id' => $balon->id,
                'titulo' => $balon->marcaModelo(),
                'descripcion' => $balon->tipoLabel().' · Bs. '.number_format((float) $balon->precio_por_hora, 2).' por hora',
                'imagen' => $balon->imagenUrl(),
                'estado' => in_array($balon->id, $ocupados, true) ? 'No disponible' : 'Disponible',
                'disabled' => in_array($balon->id, $ocupados, true),
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

    private function componenteHorarios(Cancha $cancha, string $fecha): array
    {
        $items = $this->politicaReservas->franjasParaConsulta($cancha, $fecha)
            ->map(fn (array $item) => [
                'titulo' => substr($item['horario']->hora_inicio, 0, 5).'–'.substr($item['horario']->hora_fin, 0, 5),
                'descripcion' => 'Franja de 30 minutos',
                'estado' => $item['disponible'] ? 'Disponible' : 'No disponible',
                'disabled' => ! $item['disponible'],
                'mensaje' => 'a las '.substr($item['horario']->hora_inicio, 0, 5),
            ])->all();

        return ['tipo' => 'opciones', 'entidad' => 'horario', 'items' => $items];
    }

    private function componenteDuraciones(): array
    {
        $items = collect($this->politicaReservas->configuracion()->duraciones_permitidas ?: [60, 90, 120, 150, 180])
            ->map(fn ($minutos) => [
                'titulo' => $minutos.' minutos',
                'descripcion' => number_format($minutos / 60, 1).' horas',
                'estado' => 'Elegir',
                'mensaje' => 'por '.$minutos.' minutos',
            ])->all();

        return ['tipo' => 'opciones', 'entidad' => 'duracion', 'items' => $items];
    }

    private function canchaCubreRango(Cancha $cancha, string $fecha, string $horaInicio, ?string $horaFin): bool
    {
        $libres = $this->politicaReservas->horariosLibres($cancha, $fecha);
        if (! $horaFin) {
            return $libres->contains('hora_inicio', $horaInicio);
        }

        return $this->politicaReservas->rangoLibre($cancha, $fecha, $horaInicio, $horaFin);
    }
}
