<?php

namespace App\Services\Chatbot;

use App\Models\Balon;
use App\Models\Cancha;
use App\Models\Conversacion;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use App\Services\PoliticaReservasService;
use App\Services\ReservaService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReservaConversacionalService
{
    public const CONTEXTO_BORRADOR = 'reserva_borrador';

    public const CONTEXTO_RESERVAS_CREADAS = 'reservas_creadas_chatbot';

    private const MINUTOS_VIGENCIA = 10;

    public function __construct(
        private readonly ReservaService $reservaService,
        private readonly PoliticaReservasService $politicaReservas,
    ) {}

    public function tieneBorrador(Conversacion $conversacion): bool
    {
        return is_array($conversacion->contextoDe(self::CONTEXTO_BORRADOR));
    }

    public function reiniciar(Conversacion $conversacion): void
    {
        $contexto = $conversacion->contexto ?? [];
        foreach ([
            self::CONTEXTO_BORRADOR, 'intencion_pendiente', 'cancha_id', 'fecha',
            'hora', 'hora_fin', 'duracion_minutos', 'ambiguedad_horaria',
        ] as $clave) {
            unset($contexto[$clave]);
        }
        $conversacion->update(['contexto' => $contexto]);
    }

    public function procesar(
        string $mensaje,
        Conversacion $conversacion,
        ?User $usuario,
        ?Cancha $canchaDetectada = null,
        ?string $fechaDetectada = null,
        ?string $horaDetectada = null,
        ?string $horaFinDetectada = null,
    ): string {
        if ($usuario && ($usuario->rol !== User::ROL_CLIENTE || ! $usuario->estaActivo())) {
            $this->descartarBorrador($conversacion);

            return 'La reserva conversacional está disponible únicamente para clientes activos.';
        }

        $borradorExistente = $conversacion->contextoDe(self::CONTEXTO_BORRADOR);
        if (is_array($borradorExistente) && $this->estaVencido($borradorExistente)) {
            $this->descartarBorrador($conversacion);

            return 'El borrador de la reserva venció después de 10 minutos. Dime “quiero reservar” para comenzar nuevamente.';
        }

        $borrador = is_array($borradorExistente)
            ? $borradorExistente
            : $this->nuevoBorrador($conversacion);

        if (($borrador['estado'] ?? null) === 'confirmar_cancha') {
            if ($this->esRespuestaNegativa($mensaje)) {
                unset($borrador['cancha_sugerida_id']);
                $borrador['estado'] = 'recopilando';
                $this->guardarBorrador($conversacion, $borrador);

                return $this->pedirCancha();
            }
            if ($this->esConfirmacionExplicita($mensaje) || Str::contains($this->normalizar($mensaje), ['si', 'dale', 'esta bien'])) {
                $borrador['cancha_id'] = $borrador['cancha_sugerida_id'];
                unset($borrador['cancha_sugerida_id']);
                $borrador['estado'] = 'recopilando';
            } else {
                $sugerida = Cancha::find($borrador['cancha_sugerida_id'] ?? null);

                return '¿Quieres continuar con '.($sugerida?->nombre ?? 'la cancha sugerida').'? Responde sí o elige otra cancha.';
            }
        }

        if (($borrador['estado'] ?? null) === 'confirmacion') {
            if ($this->esRespuestaNegativa($mensaje)) {
                $this->descartarBorrador($conversacion);

                return 'De acuerdo, descarté el borrador. No se registró ninguna reserva.';
            }

            if ($this->esConfirmacionExplicita($mensaje)) {
                return $this->confirmar($borrador, $conversacion, $usuario);
            }

            if (! $canchaDetectada && ! $fechaDetectada && ! $horaDetectada && ! $horaFinDetectada) {
                return $this->resumenConfirmacion($borrador)
                    .' Para registrarla debes decir “sí, confirmar”; si no la quieres, di “no”.';
            }

            $borrador['estado'] = 'recopilando';
        }

        $this->incorporarEntidades($borrador, $canchaDetectada, $fechaDetectada, $horaDetectada, $horaFinDetectada);

        $fechaInicial = $this->resolverFecha($borrador);
        if (! $fechaInicial) {
            $this->guardarBorrador($conversacion, $borrador);

            return '¿Para qué fecha deseas reservar? Si no indicas otra, puedo revisar hoy.';
        }
        if ($errorFecha = $this->politicaReservas->validarFecha($fechaInicial)) {
            unset($borrador['fecha'], $borrador['hora'], $borrador['hora_fin'], $borrador['horario_id'], $borrador['balon_id'], $borrador['balon_elegido']);
            $this->guardarBorrador($conversacion, $borrador);

            return $errorFecha->mensaje;
        }
        $prefijoCancha = $canchaDetectada ? "Perfecto, anoté *{$canchaDetectada->nombre}*. " : '';

        if (empty($borrador['hora'])) {
            $this->guardarBorrador($conversacion, $borrador);

            return $prefijoCancha.'¿A qué hora aproximadamente te gustaría empezar?';
        }
        if (empty($borrador['hora_fin'])) {
            $this->guardarBorrador($conversacion, $borrador);

            return $prefijoCancha.'¿Hasta qué hora deseas reservar o cuánto tiempo quieres jugar? Puedes indicar 60, 90 o 120 minutos.';
        }

        $cancha = $this->resolverCancha($borrador);
        if (! $cancha) {
            $fechaConsulta = $this->resolverFecha($borrador);
            $inicioConsulta = $borrador['hora'] ?? null;
            $finConsulta = $borrador['hora_fin'] ?? null;
            if ($fechaConsulta && $inicioConsulta && $finConsulta) {
                $resultado = $this->politicaReservas->consultarRango($fechaConsulta, $inicioConsulta, $finConsulta);
                if ($resultado->canchas->count() === 1) {
                    $cancha = $resultado->canchas->first();
                    $borrador['cancha_id'] = $cancha->id;
                } elseif ($resultado->canchas->count() > 1) {
                    $nombres = $resultado->canchas->pluck('nombre')->join(' y ');
                    if (Str::contains($this->normalizar($mensaje), ['cualquiera', 'me da igual', 'la que este libre', 'no importa'])) {
                        $sugerida = $resultado->canchas->first();
                        $borrador['cancha_sugerida_id'] = $sugerida->id;
                        $borrador['estado'] = 'confirmar_cancha';
                        $this->guardarBorrador($conversacion, $borrador);

                        return "{$nombres} están disponibles. ¿Quieres continuar con {$sugerida->nombre}?";
                    }
                    $this->guardarBorrador($conversacion, $borrador);

                    return "{$nombres} están disponibles durante todo el horario. ¿Cuál prefieres?";
                } else {
                    $this->guardarBorrador($conversacion, $borrador);

                    return $resultado->mensaje;
                }
            }
        }
        if (! $cancha) {
            unset($borrador['cancha_id'], $borrador['horario_id'], $borrador['balon_id'], $borrador['balon_elegido']);
            $this->guardarBorrador($conversacion, $borrador);

            return $this->pedirCancha();
        }

        $borrador['cancha_id'] = $cancha->id;

        $fecha = $this->resolverFecha($borrador);
        if (! $fecha) {
            unset($borrador['fecha'], $borrador['horario_id'], $borrador['balon_id'], $borrador['balon_elegido']);
            $this->guardarBorrador($conversacion, $borrador);

            return "Perfecto, elegiste *{$cancha->nombre}*. ¿Para qué fecha deseas reservar?";
        }

        if (Carbon::parse($fecha)->isBefore(Carbon::today())) {
            unset($borrador['fecha'], $borrador['hora'], $borrador['hora_fin'], $borrador['horario_id'], $borrador['balon_id'], $borrador['balon_elegido']);
            $this->guardarBorrador($conversacion, $borrador);

            return 'La fecha de una reserva no puede estar en el pasado. Indícame hoy u otra fecha futura.';
        }

        $borrador['fecha'] = $fecha;
        if ($errorFecha = $this->politicaReservas->validarFecha($fecha)) {
            unset($borrador['fecha'], $borrador['hora'], $borrador['hora_fin'], $borrador['horario_id'], $borrador['balon_id'], $borrador['balon_elegido']);
            $this->guardarBorrador($conversacion, $borrador);

            return $errorFecha->mensaje;
        }
        $libres = $cancha->horariosDisponiblesPara($fecha);
        if ($libres->isEmpty()) {
            unset($borrador['hora'], $borrador['hora_fin'], $borrador['horario_id'], $borrador['balon_id'], $borrador['balon_elegido']);
            $this->guardarBorrador($conversacion, $borrador);

            return 'No quedan horarios libres para *'.$cancha->nombre.'* el '
                .Carbon::parse($fecha)->translatedFormat('l d \d\e F')
                .'. Dime otra fecha o elige otra cancha.';
        }

        if (! empty($borrador['hora']) && empty($borrador['hora_fin'])) {
            $this->guardarBorrador($conversacion, $borrador);

            return '¿Hasta qué hora deseas reservar o cuánto tiempo quieres jugar? Puedes indicar 60, 90 o 120 minutos.';
        }

        $horario = $this->resolverHorario($borrador, $libres);
        if (! $horario) {
            $horaPedida = $borrador['hora'] ?? null;
            unset($borrador['hora'], $borrador['hora_fin'], $borrador['horario_id'], $borrador['balon_id'], $borrador['balon_elegido']);
            $this->guardarBorrador($conversacion, $borrador);

            $motivo = $horaPedida ? "El horario de las {$this->formatoHora($horaPedida)} en *{$cancha->nombre}* ya no está libre. " : '';

            return $motivo.$this->pedirHorario($cancha, $fecha, $libres);
        }

        $borrador['horario_id'] = $horario->id;
        $borrador['hora'] = $horario->hora_inicio;

        $horaFin = $borrador['hora_fin'] ?? $horario->hora_fin;
        $franjasRango = $this->franjasContinuas($libres, $horario->hora_inicio, $horaFin);
        if ($franjasRango->isEmpty()) {
            unset($borrador['hora'], $borrador['hora_fin'], $borrador['horario_id'], $borrador['balon_id'], $borrador['balon_elegido']);
            $this->guardarBorrador($conversacion, $borrador);

            return "El bloque de {$this->formatoHora($horario->hora_inicio)} a {$this->formatoHora($horaFin)} no está disponible de forma continua en *{$cancha->nombre}*. ".$this->pedirHorario($cancha, $fecha, $libres);
        }
        $borrador['hora_fin'] = $horaFin;

        if (! array_key_exists('balon_elegido', $borrador)) {
            $seleccion = $this->resolverSeleccionBalon($mensaje, $fecha, $horario, $horaFin);
            if ($seleccion['seleccionado']) {
                $borrador['balon_elegido'] = true;
                $borrador['balon_id'] = $seleccion['balon_id'];
            } else {
                $this->guardarBorrador($conversacion, $borrador);

                return $this->pedirBalon($fecha, $horario, $horaFin, $seleccion['mensaje']);
            }
        }

        $borrador['estado'] = 'confirmacion';
        $this->guardarBorrador($conversacion, $borrador);

        $siguientePaso = $usuario
            ? 'Di “sí, confirmar” para registrarla o “no” para descartarla.'
            : 'Para registrarla necesitas iniciar sesión como cliente y luego decir “sí, confirmar”; también puedes decir “no” para descartarla.';

        return $this->resumenConfirmacion($borrador).' '.$siguientePaso;
    }

    /**
     * @return array<string, mixed>
     */
    private function nuevoBorrador(Conversacion $conversacion): array
    {
        $borrador = ['estado' => 'recopilando'];

        foreach (['cancha_id', 'fecha', 'hora', 'hora_fin'] as $clave) {
            $valor = $conversacion->contextoDe($clave);
            if ($valor !== null) {
                $borrador[$clave] = $valor;
            }
        }

        return $borrador;
    }

    /**
     * @param  array<string, mixed>  $borrador
     */
    private function incorporarEntidades(
        array &$borrador,
        ?Cancha $cancha,
        ?string $fecha,
        ?string $hora,
        ?string $horaFin,
    ): void {
        if ($cancha && ($borrador['cancha_id'] ?? null) !== $cancha->id) {
            $borrador['cancha_id'] = $cancha->id;
            unset($borrador['horario_id'], $borrador['balon_id'], $borrador['balon_elegido']);
        }

        if ($fecha && ($borrador['fecha'] ?? null) !== $fecha) {
            $borrador['fecha'] = $fecha;
            if (! $hora) {
                unset($borrador['hora'], $borrador['hora_fin']);
            }
            unset($borrador['horario_id'], $borrador['balon_id'], $borrador['balon_elegido']);
        }

        if ($hora && ($borrador['hora'] ?? null) !== $hora) {
            $borrador['hora'] = $hora;
            if (! $horaFin) {
                unset($borrador['hora_fin']);
            }
            unset($borrador['horario_id'], $borrador['balon_id'], $borrador['balon_elegido']);
        }

        if ($horaFin && ($borrador['hora_fin'] ?? null) !== $horaFin) {
            $borrador['hora_fin'] = $horaFin;
            unset($borrador['horario_id'], $borrador['balon_id'], $borrador['balon_elegido']);
        }

        $borrador['estado'] = 'recopilando';
    }

    /**
     * @param  array<string, mixed>  $borrador
     */
    private function resolverCancha(array $borrador): ?Cancha
    {
        $id = $borrador['cancha_id'] ?? null;

        return $id ? Cancha::disponibles()->find($id) : null;
    }

    /**
     * @param  array<string, mixed>  $borrador
     */
    private function resolverFecha(array $borrador): ?string
    {
        $fecha = $borrador['fecha'] ?? null;
        if (! is_string($fecha) || ! $this->reservaService->esFechaValida($fecha)) {
            return null;
        }

        try {
            $fechaValida = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
            $errores = \DateTimeImmutable::getLastErrors();
            if (! $fechaValida
                || ($errores !== false && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))
                || $fechaValida->format('Y-m-d') !== $fecha) {
                return null;
            }

            return $fechaValida->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $borrador
     * @param  Collection<int, Horario>  $libres
     */
    private function resolverHorario(array $borrador, Collection $libres): ?Horario
    {
        if ($id = $borrador['horario_id'] ?? null) {
            return $libres->firstWhere('id', (int) $id);
        }

        $hora = $borrador['hora'] ?? null;

        return $hora ? $libres->firstWhere('hora_inicio', $hora) : null;
    }

    /**
     * @return array{seleccionado: bool, balon_id: ?int, mensaje: ?string}
     */
    private function resolverSeleccionBalon(string $mensaje, string $fecha, Horario $horario, string $horaFin): array
    {
        $texto = $this->normalizar($mensaje);

        if (Str::contains($texto, ['balon propio', 'mi balon', 'traigo mi balon', 'llevo mi balon'])) {
            return ['seleccionado' => true, 'balon_id' => null, 'mensaje' => null];
        }

        $disponibles = $this->balonesDisponibles($fecha, $horario, $horaFin);
        foreach ($disponibles as $balon) {
            $nombreCompleto = $this->normalizar($balon->marcaModelo());
            if ($nombreCompleto !== '' && Str::contains($texto, $nombreCompleto)) {
                return ['seleccionado' => true, 'balon_id' => $balon->id, 'mensaje' => null];
            }
        }

        $tipo = null;
        if (Str::contains($texto, ['premium'])) {
            $tipo = Balon::TIPO_PREMIUM;
        } elseif (Str::contains($texto, ['prestado', 'economico'])) {
            $tipo = Balon::TIPO_ECONOMICO;
        }

        if ($tipo) {
            $delTipo = $disponibles->where('tipo', $tipo)->values();
            if ($delTipo->count() === 1) {
                return ['seleccionado' => true, 'balon_id' => $delTipo->first()->id, 'mensaje' => null];
            }

            if ($delTipo->isEmpty()) {
                return [
                    'seleccionado' => false,
                    'balon_id' => null,
                    'mensaje' => 'No hay balones '.($tipo === Balon::TIPO_PREMIUM ? 'premium' : 'prestados').' libres para esa hora.',
                ];
            }

            return [
                'seleccionado' => false,
                'balon_id' => null,
                'mensaje' => 'Hay más de una opción; indica la marca y el nombre del balón.',
            ];
        }

        return ['seleccionado' => false, 'balon_id' => null, 'mensaje' => null];
    }

    /**
     * @return Collection<int, Balon>
     */
    private function balonesDisponibles(string $fecha, Horario $horario, ?string $horaFin = null): Collection
    {
        $ocupados = $this->reservaService->balonesOcupados($fecha, $horario, $horaFin);

        return Balon::query()
            ->when($ocupados, fn ($query) => $query->whereNotIn('id', $ocupados))
            ->orderBy('tipo')
            ->orderBy('marca')
            ->orderBy('nombre')
            ->get();
    }

    private function pedirCancha(): string
    {
        $nombres = Cancha::disponibles()->orderBy('nombre')->pluck('nombre')->join(', ');

        return '¿Qué cancha deseas reservar?'.($nombres !== '' ? " Tenemos: {$nombres}." : '');
    }

    /**
     * @param  Collection<int, Horario>  $libres
     */
    private function pedirHorario(Cancha $cancha, string $fecha, Collection $libres): string
    {
        $horas = $libres
            ->map(fn (Horario $horario) => $this->formatoHora($horario->hora_inicio).' – '.$this->formatoHora($horario->hora_fin))
            ->join(', ');

        return 'Los horarios libres para *'.$cancha->nombre.'* el '
            .Carbon::parse($fecha)->translatedFormat('l d \d\e F')
            ." son: {$horas}. ¿Cuál prefieres?";
    }

    private function pedirBalon(string $fecha, Horario $horario, string $horaFin, ?string $aviso = null): string
    {
        $opciones = $this->balonesDisponibles($fecha, $horario, $horaFin)
            ->map(fn (Balon $balon) => $balon->marcaModelo().' ('.$balon->tipoLabel().', Bs. '
                .number_format((float) $balon->precio_por_hora, 2).')')
            ->join('; ');

        $respuesta = 'Horario seleccionado: '.$this->formatoHora($horario->hora_inicio).' – '
            .$this->formatoHora($horaFin).'. ';
        $respuesta .= $aviso ? $aviso.' ' : '';
        $respuesta .= '¿Usarás balón propio, prestado o premium?';

        return $opciones !== '' ? $respuesta." Opciones libres: {$opciones}." : $respuesta.' Por ahora solo está disponible balón propio.';
    }

    /**
     * @param  array<string, mixed>  $borrador
     */
    private function resumenConfirmacion(array $borrador): string
    {
        $cancha = Cancha::find($borrador['cancha_id'] ?? null);
        $horario = Horario::find($borrador['horario_id'] ?? null);
        $balon = filled($borrador['balon_id'] ?? null) ? Balon::find($borrador['balon_id']) : null;

        if (! $cancha || ! $horario || empty($borrador['fecha'])) {
            return 'No pude reconstruir todos los datos del borrador.';
        }

        $horaFin = $borrador['hora_fin'] ?? $horario->hora_fin;
        $duracion = Carbon::parse($horario->hora_inicio)->diffInMinutes(Carbon::parse($horaFin));
        $monto = $this->reservaService->calcularMonto((float) $cancha->precio_por_hora, $balon?->id, $duracion);
        $balonTexto = $balon ? $balon->marcaModelo().' ('.$balon->tipoLabel().')' : Reserva::OPCIONES_BALON[Reserva::BALON_PROPIO];

        return "Revisa tu reserva:\n"
            ."• Cancha: {$cancha->nombre}\n"
            .'• Fecha: '.Carbon::parse($borrador['fecha'])->translatedFormat('l d \d\e F \d\e Y')."\n"
            .'• Horario: '.$this->formatoHora($horario->hora_inicio).' – '.$this->formatoHora($horaFin)."\n"
            ."• Balón: {$balonTexto}\n"
            .'• Total: Bs. '.number_format($monto, 2).'.';
    }

    /**
     * @param  array<string, mixed>  $borrador
     */
    private function confirmar(array $borrador, Conversacion $conversacion, ?User $usuario): string
    {
        if (! $usuario) {
            return 'Para confirmar y registrar este borrador necesitas iniciar sesión como cliente. 🔐';
        }

        if ($usuario->rol !== User::ROL_CLIENTE || ! $usuario->estaActivo()) {
            $this->descartarBorrador($conversacion);

            return 'La reserva conversacional está disponible únicamente para clientes activos.';
        }

        $cancha = Cancha::disponibles()->find($borrador['cancha_id'] ?? null);
        $fecha = $borrador['fecha'] ?? null;
        $horario = $cancha?->horarios()->where('disponible', true)->find($borrador['horario_id'] ?? null);

        if (! $cancha || ! is_string($fecha) || ! $horario || Carbon::parse($fecha)->isBefore(Carbon::today())) {
            $this->descartarBorrador($conversacion);

            return 'Los datos del borrador ya no son válidos. No se registró la reserva; comienza nuevamente.';
        }

        $horaFin = $borrador['hora_fin'] ?? $horario->hora_fin;
        $libresActuales = $cancha->horariosDisponiblesPara($fecha);
        $libre = $this->franjasContinuas($libresActuales, $horario->hora_inicio, $horaFin)->isNotEmpty();
        if (! $libre) {
            unset($borrador['hora'], $borrador['hora_fin'], $borrador['horario_id'], $borrador['balon_id'], $borrador['balon_elegido']);
            $borrador['estado'] = 'recopilando';
            $this->guardarBorrador($conversacion, $borrador);

            $alternativas = $cancha->horariosDisponiblesPara($fecha);

            return 'Ese horario acaba de dejar de estar disponible. No se registró la reserva. '
                .($alternativas->isNotEmpty()
                    ? $this->pedirHorario($cancha, $fecha, $alternativas)
                    : 'Dime otra fecha para buscar alternativas.');
        }

        $balonId = $borrador['balon_id'] ?? null;
        if ($balonId) {
            $balon = Balon::find($balonId);
            if (! $balon || $this->reservaService->balonOcupado($balon->id, $fecha, $horario, $horaFin)) {
                unset($borrador['balon_id'], $borrador['balon_elegido']);
                $borrador['estado'] = 'recopilando';
                $this->guardarBorrador($conversacion, $borrador);

                return 'El balón elegido acaba de dejar de estar disponible. No se registró la reserva. '
                    .$this->pedirBalon($fecha, $horario, $horaFin);
            }
        }

        try {
            $reserva = $this->reservaService->crearReserva($usuario, [
                'cancha_id' => $cancha->id,
                'horario_id' => $horario->id,
                'fecha' => $fecha,
                'hora_fin' => $horaFin,
                'balon_id' => $balonId,
                'observaciones' => null,
            ]);
        } catch (HttpException $exception) {
            if (in_array($exception->getStatusCode(), [409, 422], true)) {
                $this->descartarBorrador($conversacion);

                return 'No pude registrar la reserva porque la disponibilidad cambió: '
                    .$exception->getMessage().' Consulta nuevamente los horarios libres.';
            }

            throw $exception;
        }

        $this->descartarBorrador($conversacion);
        $this->registrarReservaCreada($conversacion, $reserva);

        return "Reserva #{$reserva->id} registrada correctamente y pendiente de confirmación. Puedes verla en Mis reservas.";
    }

    /**
     * @param  array<string, mixed>  $borrador
     */
    private function guardarBorrador(Conversacion $conversacion, array $borrador): void
    {
        $contexto = $conversacion->contexto ?? [];
        $borrador['expires_at'] = now()->addMinutes(self::MINUTOS_VIGENCIA)->toIso8601String();
        $contexto[self::CONTEXTO_BORRADOR] = $borrador;
        $contexto['intencion_pendiente'] = DetectorIntencion::HACER_RESERVA;
        $conversacion->update(['contexto' => $contexto]);
    }

    private function descartarBorrador(Conversacion $conversacion): void
    {
        $contexto = $conversacion->contexto ?? [];
        unset($contexto[self::CONTEXTO_BORRADOR]);
        if (($contexto['intencion_pendiente'] ?? null) === DetectorIntencion::HACER_RESERVA) {
            unset($contexto['intencion_pendiente']);
        }
        foreach (['hora', 'hora_fin', 'duracion_minutos', 'horario_id', 'balon_id', 'balon_elegido', 'cancha_sugerida_id', 'alternativa_sugerida', 'dato_pendiente', 'resultado_disponibilidad'] as $clave) {
            unset($contexto[$clave]);
        }
        $conversacion->update(['contexto' => $contexto]);
    }

    private function registrarReservaCreada(Conversacion $conversacion, Reserva $reserva): void
    {
        $contexto = $conversacion->contexto ?? [];
        $registros = $contexto[self::CONTEXTO_RESERVAS_CREADAS] ?? [];
        $registros = is_array($registros) ? $registros : [];
        $registros[] = [
            'reserva_id' => $reserva->id,
            'created_at' => $reserva->created_at->toIso8601String(),
        ];

        $contexto[self::CONTEXTO_RESERVAS_CREADAS] = array_values($registros);
        $conversacion->update(['contexto' => $contexto]);
    }

    /**
     * @param  array<string, mixed>  $borrador
     */
    private function estaVencido(array $borrador): bool
    {
        try {
            return empty($borrador['expires_at']) || Carbon::parse($borrador['expires_at'])->isPast();
        } catch (\Throwable) {
            return true;
        }
    }

    public function esConfirmacionExplicita(string $mensaje): bool
    {
        $texto = $this->normalizar($mensaje);

        return in_array($texto, [
            'si confirmar', 'si confirmo', 'confirmar', 'confirmo', 'confirmar reserva',
            'de acuerdo confirmar', 'correcto confirmar', 'acepto confirmar',
        ], true);
    }

    public function esRespuestaNegativa(string $mensaje): bool
    {
        $texto = $this->normalizar($mensaje);

        return in_array($texto, ['no', 'no gracias', 'cancelar', 'descartar', 'mejor no', 'no confirmar'], true);
    }

    private function normalizar(string $texto): string
    {
        $texto = Str::lower(Str::ascii($texto));
        $texto = preg_replace('/[^a-z0-9\s]/u', ' ', $texto) ?? '';
        $texto = preg_replace('/\s+/', ' ', $texto) ?? '';

        return trim($texto);
    }

    private function formatoHora(string $hora): string
    {
        return Str::substr($hora, 0, 5);
    }

    /**
     * Devuelve las franjas que cubren exactamente un rango continuo.
     *
     * @param  Collection<int, Horario>  $libres
     * @return Collection<int, Horario>
     */
    private function franjasContinuas(Collection $libres, string $horaInicio, string $horaFin): Collection
    {
        $resultado = collect();
        $siguiente = $horaInicio;

        foreach ($libres->where('hora_inicio', '>=', $horaInicio)->sortBy('hora_inicio') as $franja) {
            if ($franja->hora_inicio !== $siguiente || $franja->hora_fin > $horaFin) {
                break;
            }

            $resultado->push($franja);
            $siguiente = $franja->hora_fin;
            if ($siguiente === $horaFin) {
                return $resultado;
            }
        }

        return collect();
    }
}
