<?php

namespace App\Services;

use App\Models\Cancha;
use App\Models\CierreCancha;
use App\Models\ConfiguracionReserva;
use App\Models\ExcepcionReserva;
use App\Models\Horario;
use App\Models\HorarioSemanal;
use App\Models\Reserva;
use App\Services\Reservas\ResultadoDisponibilidad;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PoliticaReservasService
{
    public function configuracion(): ConfiguracionReserva
    {
        return ConfiguracionReserva::actual();
    }

    public function zonaHoraria(): string
    {
        return $this->configuracion()->zona_horaria ?: (string) config('app.timezone', 'America/La_Paz');
    }

    public function hoy(): Carbon
    {
        return Carbon::now($this->zonaHoraria())->startOfDay();
    }

    public function fechaValida(?string $fecha): bool
    {
        if (! is_string($fecha) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) !== 1) {
            return false;
        }

        $valor = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        $errores = DateTimeImmutable::getLastErrors();

        return $valor !== false
            && ($errores === false || ($errores['warning_count'] === 0 && $errores['error_count'] === 0))
            && $valor->format('Y-m-d') === $fecha;
    }

    public function validarFecha(string $fecha): ?ResultadoDisponibilidad
    {
        if (! $this->fechaValida($fecha)) {
            return new ResultadoDisponibilidad(ResultadoDisponibilidad::FECHA_INVALIDA, 'La fecha indicada no es válida.');
        }

        $config = $this->configuracion();
        $dia = Carbon::parse($fecha, $this->zonaHoraria())->startOfDay();
        $hoy = $this->hoy();

        if ($dia->lt($hoy)) {
            return new ResultadoDisponibilidad(ResultadoDisponibilidad::FECHA_PASADA, 'Esa fecha ya pasó y no puede utilizarse para una nueva reserva.');
        }
        if ($dia->isSameDay($hoy) && ! $config->permitir_hoy) {
            return new ResultadoDisponibilidad(ResultadoDisponibilidad::FUERA_DE_RANGO, 'Las reservas para hoy no están habilitadas.');
        }
        if ($dia->gt($hoy) && ! $config->permitir_futuras) {
            return new ResultadoDisponibilidad(ResultadoDisponibilidad::FUERA_DE_RANGO, 'Las reservas para fechas futuras no están habilitadas.');
        }
        if ($dia->gt($hoy->copy()->addDays((int) $config->anticipacion_maxima_dias))) {
            return new ResultadoDisponibilidad(
                ResultadoDisponibilidad::FUERA_DE_RANGO,
                'Actualmente puedes reservar hasta con '.$config->anticipacion_maxima_dias.' días de anticipación.'
            );
        }

        return null;
    }

    public function horarioSemanal(string $fecha): ?HorarioSemanal
    {
        if (! Schema::hasTable('horarios_semanales')) {
            return null;
        }

        $dia = Carbon::parse($fecha, $this->zonaHoraria())->dayOfWeekIso;

        return HorarioSemanal::query()->where('dia_semana', $dia)->first();
    }

    public function validarRangoOperativo(Cancha $cancha, string $fecha, string $horaInicio, string $horaFin): ResultadoDisponibilidad
    {
        if ($error = $this->validarFecha($fecha)) {
            return $error;
        }

        $inicio = $this->normalizarHora($horaInicio);
        $fin = $this->normalizarHora($horaFin);
        if (! $inicio || ! $fin || $fin <= $inicio) {
            return new ResultadoDisponibilidad(ResultadoDisponibilidad::DATOS_INSUFICIENTES, 'Debes indicar una hora inicial y final válidas.');
        }

        $semanal = $this->horarioSemanal($fecha);
        if ($semanal && (! $semanal->abierto || ! $semanal->hora_apertura || ! $semanal->hora_cierre)) {
            return new ResultadoDisponibilidad(ResultadoDisponibilidad::DIA_CERRADO, 'Ese día el complejo no tendrá atención.');
        }
        $apertura = $semanal ? $this->normalizarHora($semanal->hora_apertura) : null;
        $cierre = $semanal ? $this->normalizarHora($semanal->hora_cierre) : null;
        if ($semanal && ($inicio < $apertura || $fin > $cierre)) {
            return new ResultadoDisponibilidad(
                ResultadoDisponibilidad::FUERA_DE_HORARIO,
                'El horario de atención para ese día es de '.substr($semanal->hora_apertura, 0, 5).' a '.substr($semanal->hora_cierre, 0, 5).'.'
            );
        }

        if (! $cancha->estaDisponible()) {
            return new ResultadoDisponibilidad(ResultadoDisponibilidad::CANCHA_NO_HABILITADA, 'La cancha no está habilitada para reservas.');
        }

        if ($this->antesDeSiguienteHora($fecha, $inicio)) {
            return new ResultadoDisponibilidad(ResultadoDisponibilidad::HORARIO_PASADO, 'Para hoy, elige una franja que comience desde la siguiente hora completa.');
        }

        if ($this->bloqueadoPorExcepcion($cancha->id, $fecha, $inicio, $fin)) {
            return new ResultadoDisponibilidad(ResultadoDisponibilidad::BLOQUEADO, 'Ese horario no está disponible debido a una excepción o bloqueo programado.');
        }

        return new ResultadoDisponibilidad(
            ResultadoDisponibilidad::DISPONIBLE,
            'El rango cumple las reglas operativas.',
            new Collection([$cancha]),
            [],
            $fecha,
            $inicio,
            $fin,
        );
    }

    /** @return EloquentCollection<int, Horario> */
    public function horariosLibres(Cancha $cancha, string $fecha): EloquentCollection
    {
        if ($this->validarFecha($fecha) || ! $cancha->estaDisponible()) {
            return new EloquentCollection;
        }

        $semanal = $this->horarioSemanal($fecha);
        if ($semanal && ! $semanal->abierto) {
            return new EloquentCollection;
        }
        if (CierreCancha::query()->where('cancha_id', $cancha->id)->whereDate('fecha', $fecha)->exists()) {
            return new EloquentCollection;
        }

        $diaSemana = Carbon::parse($fecha, $this->zonaHoraria())->dayOfWeekIso;
        $horarios = $cancha->horarios()
            ->where('dia_semana', $diaSemana)
            ->where('disponible', true)
            ->where(fn ($query) => $query->whereNull('vigente_desde')->orWhereDate('vigente_desde', '<=', $fecha))
            ->where(fn ($query) => $query->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $fecha))
            ->orderBy('hora_inicio')
            ->get();

        $reservas = Reserva::query()->activas()->deCanchaEnFecha($cancha->id, $fecha)
            ->get(['hora_inicio_reserva', 'hora_fin_reserva']);
        $excepciones = Schema::hasTable('excepciones_reserva')
            ? ExcepcionReserva::query()->whereDate('fecha', $fecha)
                ->where('estado', ExcepcionReserva::ESTADO_APLICADA)
                ->where(fn ($q) => $q->whereNull('cancha_id')->orWhere('cancha_id', $cancha->id))->get()
            : collect();

        return $horarios->reject(function (Horario $horario) use ($cancha, $fecha, $semanal, $reservas, $excepciones): bool {
            $apertura = $semanal ? $this->normalizarHora($semanal->hora_apertura) : null;
            $cierre = $semanal ? $this->normalizarHora($semanal->hora_cierre) : null;
            if ($semanal && ($horario->hora_inicio < $apertura || $horario->hora_fin > $cierre)) {
                return true;
            }
            if ($this->antesDeSiguienteHora($fecha, $horario->hora_inicio)) {
                return true;
            }
            if ($this->bloqueadoPorExcepcion($cancha->id, $fecha, $horario->hora_inicio, $horario->hora_fin, $excepciones)) {
                return true;
            }

            return $reservas->contains(fn (Reserva $reserva) =>
                $reserva->hora_inicio_reserva < $horario->hora_fin
                && $reserva->hora_fin_reserva > $horario->hora_inicio);
        })->values();
    }

    /** Franjas configuradas con su estado; solo se omiten horas ya transcurridas de hoy. */
    public function franjasParaConsulta(Cancha $cancha, string $fecha): Collection
    {
        if ($this->validarFecha($fecha)) {
            return collect();
        }

        $semanal = $this->horarioSemanal($fecha);
        if ($semanal && ! $semanal->abierto) {
            return collect();
        }

        $dia = Carbon::parse($fecha, $this->zonaHoraria())->dayOfWeekIso;
        $libres = $this->horariosLibres($cancha, $fecha)->pluck('id')->all();

        return $cancha->horarios()->where('dia_semana', $dia)
            ->where(fn ($q) => $q->whereNull('vigente_desde')->orWhereDate('vigente_desde', '<=', $fecha))
            ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $fecha))
            ->orderBy('hora_inicio')->get()
            ->filter(function (Horario $franja) use ($fecha, $semanal): bool {
                if ($this->antesDeSiguienteHora($fecha, $franja->hora_inicio)) {
                    return false;
                }

                return ! $semanal || ($franja->hora_inicio >= $semanal->hora_apertura
                    && $franja->hora_fin <= $semanal->hora_cierre);
            })
            ->map(fn (Horario $franja) => [
                'horario' => $franja,
                'disponible' => $franja->disponible && in_array($franja->id, $libres, true),
            ])->values();
    }

    public function antesDeSiguienteHora(string $fecha, string $horaInicio): bool
    {
        $ahora = Carbon::now($this->zonaHoraria());
        if ($fecha !== $ahora->toDateString()) {
            return false;
        }

        $limite = $ahora->copy()->startOfHour()->addHour();

        return Carbon::parse($fecha.' '.$horaInicio, $this->zonaHoraria())->lt($limite);
    }

    public function consultarRango(string $fecha, string $horaInicio, string $horaFin, ?Cancha $cancha = null): ResultadoDisponibilidad
    {
        $canchas = $cancha ? new Collection([$cancha]) : Cancha::disponibles()->orderBy('nombre')->get();
        $disponibles = new Collection;
        $primerError = null;

        foreach ($canchas as $candidata) {
            $operativo = $this->validarRangoOperativo($candidata, $fecha, $horaInicio, $horaFin);
            if (! $operativo->disponible()) {
                $primerError ??= $operativo;

                continue;
            }
            if ($this->rangoLibre($candidata, $fecha, $horaInicio, $horaFin)) {
                $disponibles->push($candidata);
            }
        }

        if ($disponibles->isNotEmpty()) {
            return new ResultadoDisponibilidad(
                ! $cancha && $disponibles->count() === 1
                    ? ResultadoDisponibilidad::UNA_CANCHA
                    : ResultadoDisponibilidad::DISPONIBLE,
                'Hay disponibilidad completa.',
                $disponibles,
                [],
                $fecha,
                $this->normalizarHora($horaInicio),
                $this->normalizarHora($horaFin),
            );
        }

        if ($primerError && in_array($primerError->estado, [
            ResultadoDisponibilidad::FECHA_INVALIDA, ResultadoDisponibilidad::FECHA_PASADA,
            ResultadoDisponibilidad::FUERA_DE_RANGO, ResultadoDisponibilidad::DIA_CERRADO,
            ResultadoDisponibilidad::FUERA_DE_HORARIO,
        ], true)) {
            return $primerError;
        }

        $alternativas = $this->alternativas($canchas, $fecha, $horaInicio, $horaFin);

        $mensaje = $alternativas
            ? ($primerError?->estado === ResultadoDisponibilidad::HORARIO_PASADO
                ? 'Ese horario ya pasó o comenzó. Estas son las opciones futuras más cercanas.'
                : 'No hay disponibilidad completa, pero existen alternativas.')
            : ($primerError?->mensaje ?? 'No hay ninguna cancha disponible durante todo ese horario.');

        return new ResultadoDisponibilidad(
            $alternativas ? ResultadoDisponibilidad::PARCIAL : ResultadoDisponibilidad::SIN_DISPONIBILIDAD,
            $mensaje,
            new Collection,
            $alternativas,
            $fecha,
            $this->normalizarHora($horaInicio),
            $this->normalizarHora($horaFin),
        );
    }

    public function rangoLibre(Cancha $cancha, string $fecha, string $horaInicio, string $horaFin): bool
    {
        $inicio = $this->normalizarHora($horaInicio);
        $fin = $this->normalizarHora($horaFin);
        if (! $inicio || ! $fin) {
            return false;
        }

        $libres = $this->horariosLibres($cancha, $fecha);
        $siguiente = $inicio;
        foreach ($libres->where('hora_inicio', '>=', $inicio)->sortBy('hora_inicio') as $franja) {
            if ($franja->hora_inicio !== $siguiente || $franja->hora_fin > $fin) {
                return false;
            }
            $siguiente = $franja->hora_fin;
            if ($siguiente === $fin) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, Cancha>  $canchas
     * @return array<int, array{inicio: string, fin: string, cancha_id: int, tipo?: string}>
     */
    public function alternativas(Collection $canchas, string $fecha, string $horaInicio, string $horaFin): array
    {
        $duracion = Carbon::parse($horaInicio)->diffInMinutes(Carbon::parse($horaFin));
        $opciones = [];
        foreach ($canchas as $cancha) {
            $libres = $this->horariosLibres($cancha, $fecha)->sortBy('hora_inicio')->values();
            $segmento = null;
            foreach ($libres as $franja) {
                if ($franja->hora_fin <= $horaInicio || $franja->hora_inicio >= $horaFin) {
                    continue;
                }
                $inicioParcial = max($franja->hora_inicio, $this->normalizarHora($horaInicio));
                $finParcial = min($franja->hora_fin, $this->normalizarHora($horaFin));
                if ($segmento && $segmento['fin'] === $inicioParcial) {
                    $segmento['fin'] = $finParcial;
                } else {
                    if ($segmento) {
                        $opciones[] = $segmento;
                    }
                    $segmento = ['inicio' => $inicioParcial, 'fin' => $finParcial, 'cancha_id' => $cancha->id, 'tipo' => 'parcial'];
                }
            }
            if ($segmento) {
                $opciones[] = $segmento;
            }

            foreach ($libres as $franja) {
                $inicio = $franja->hora_inicio;
                $fin = Carbon::parse($inicio)->addMinutes($duracion)->format('H:i:s');
                if ($this->rangoLibre($cancha, $fecha, $inicio, $fin)) {
                    $opciones[] = ['inicio' => $inicio, 'fin' => $fin, 'cancha_id' => $cancha->id, 'tipo' => 'completa'];
                }
            }
        }

        $opciones = collect($opciones)->unique(fn (array $opcion) => $opcion['cancha_id'].'-'.$opcion['inicio'].'-'.$opcion['fin'])->values()->all();

        usort($opciones, function (array $a, array $b) use ($horaInicio): int {
            $distA = abs(Carbon::parse($horaInicio)->diffInMinutes(Carbon::parse($a['inicio']), false));
            $distB = abs(Carbon::parse($horaInicio)->diffInMinutes(Carbon::parse($b['inicio']), false));
            if ($distA === $distB) {
                return $a['inicio'] < $b['inicio'] ? 1 : -1;
            }

            return $distA <=> $distB;
        });

        if ($opciones === []) {
            $siguiente = $this->buscarBloqueContinuo(
                $fecha,
                $horaInicio,
                $horaFin,
                $canchas->count() === 1 ? $canchas->first() : null,
                'despues'
            );
            if ($siguiente) {
                $opciones[] = $siguiente + ['tipo' => 'fecha_siguiente'];
            }
        }

        return array_slice($opciones, 0, 4);
    }

    /**
     * @return array{fecha:string,inicio:string,fin:string,cancha_id:int}|null
     */
    public function buscarBloqueContinuo(
        string $fecha,
        string $horaInicio,
        string $horaFin,
        ?Cancha $cancha = null,
        string $direccion = 'cercana',
    ): ?array {
        if ($this->validarFecha($fecha)) {
            return null;
        }

        $inicioSolicitado = Carbon::parse($fecha.' '.$this->normalizarHora($horaInicio), $this->zonaHoraria());
        $finSolicitado = Carbon::parse($fecha.' '.$this->normalizarHora($horaFin), $this->zonaHoraria());
        $duracion = (int) $inicioSolicitado->diffInMinutes($finSolicitado);
        if ($duracion <= 0) {
            return null;
        }

        $canchas = $cancha ? collect([$cancha]) : Cancha::disponibles()->orderBy('nombre')->get();
        $maximo = (int) $this->configuracion()->anticipacion_maxima_dias;
        $opciones = [];
        foreach (range(0, $maximo) as $desplazamiento) {
            if ($direccion === 'antes' && $desplazamiento > 0) {
                break;
            }
            $fechaCandidata = Carbon::parse($fecha, $this->zonaHoraria())->addDays($desplazamiento)->toDateString();
            if ($this->validarFecha($fechaCandidata)) {
                continue;
            }
            foreach ($canchas as $candidata) {
                foreach ($this->horariosLibres($candidata, $fechaCandidata) as $franja) {
                    $inicio = $franja->hora_inicio;
                    $fin = Carbon::parse($inicio)->addMinutes($duracion)->format('H:i:s');
                    if (! $this->rangoLibre($candidata, $fechaCandidata, $inicio, $fin)) {
                        continue;
                    }

                    $inicioFecha = Carbon::parse($fechaCandidata.' '.$inicio, $this->zonaHoraria());
                    $finFecha = Carbon::parse($fechaCandidata.' '.$fin, $this->zonaHoraria());
                    if ($direccion === 'despues' && $inicioFecha->lt($finSolicitado)) {
                        continue;
                    }
                    if ($direccion === 'antes' && $finFecha->gt($inicioSolicitado)) {
                        continue;
                    }

                    $opciones[] = [
                        'fecha' => $fechaCandidata,
                        'inicio' => $inicio,
                        'fin' => $fin,
                        'cancha_id' => $candidata->id,
                        'distancia' => abs($inicioSolicitado->diffInMinutes($inicioFecha, false)),
                        'posterior' => $inicioFecha->gte($inicioSolicitado),
                    ];
                }
            }
        }

        usort($opciones, function (array $a, array $b): int {
            if ($a['distancia'] === $b['distancia']) {
                return ($b['posterior'] <=> $a['posterior']) ?: ($a['inicio'] <=> $b['inicio']);
            }

            return $a['distancia'] <=> $b['distancia'];
        });

        if ($opciones === []) {
            return null;
        }

        return collect($opciones[0])->except(['distancia', 'posterior'])->all();
    }

    private function bloqueadoPorExcepcion(int $canchaId, string $fecha, string $inicio, string $fin, ?Collection $excepciones = null): bool
    {
        if (! Schema::hasTable('excepciones_reserva')) {
            return false;
        }

        $excepciones ??= ExcepcionReserva::query()
            ->whereDate('fecha', $fecha)
            ->where('estado', ExcepcionReserva::ESTADO_APLICADA)
            ->where(fn ($query) => $query->whereNull('cancha_id')->orWhere('cancha_id', $canchaId))
            ->get();

        foreach ($excepciones as $excepcion) {
            if ($excepcion->tipo === ExcepcionReserva::HORARIO_ESPECIAL) {
                if (! $excepcion->hora_inicio || ! $excepcion->hora_fin
                    || $inicio < $excepcion->hora_inicio || $fin > $excepcion->hora_fin) {
                    return true;
                }

                continue;
            }

            if (! $excepcion->hora_inicio || ! $excepcion->hora_fin) {
                return true;
            }

            if ($inicio < $excepcion->hora_fin && $fin > $excepcion->hora_inicio) {
                return true;
            }
        }

        return false;
    }

    private function esHorarioPasado(string $fecha, string $horaInicio): bool
    {
        $inicio = Carbon::parse($fecha.' '.$horaInicio, $this->zonaHoraria());

        return $inicio->lte(Carbon::now($this->zonaHoraria()));
    }

    private function normalizarHora(string $hora): ?string
    {
        try {
            return Carbon::createFromFormat(strlen($hora) === 5 ? '!H:i' : '!H:i:s', $hora)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
