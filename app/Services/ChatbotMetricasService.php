<?php

namespace App\Services;

use App\Models\ChatbotEjecucion;
use App\Models\Conversacion;
use App\Models\MensajeChatbot;
use App\Services\Chatbot\DetectorIntencion;
use App\Services\Chatbot\ReservaConversacionalService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class ChatbotMetricasService
{
    public const PERIODOS = [
        '7' => '7 días',
        '30' => '30 días',
        '90' => '90 días',
        'total' => 'Todo el período',
    ];

    /**
     * Métricas operativas derivadas únicamente de datos verificables.
     *
     * @return array<string, mixed>
     */
    public function panel(string $periodo = '30'): array
    {
        $periodo = array_key_exists($periodo, self::PERIODOS) ? $periodo : '30';
        $desde = $periodo === 'total'
            ? null
            : now()->subDays(((int) $periodo) - 1)->startOfDay();
        $mensajesUsuario = $this->mensajesUsuario($desde);
        $idsConversaciones = (clone $mensajesUsuario)
            ->distinct()
            ->pluck('conversacion_id');
        $conversaciones = Conversacion::query()
            ->whereIn('id', $idsConversaciones)
            ->get(['id', 'user_id', 'contexto']);

        $totalConversaciones = $idsConversaciones->count();
        $totalMensajes = (clone $mensajesUsuario)->count();
        $bloqueadas = (clone $mensajesUsuario)
            ->where('intencion', DetectorIntencion::SOLICITUD_BLOQUEADA)
            ->count();
        $noResueltas = (clone $mensajesUsuario)
            ->where('intencion', DetectorIntencion::NO_DETECTADA)
            ->count();
        $conversiones = $this->conversiones($conversaciones, $desde);
        $conversacionesConvertidas = $conversiones->pluck('conversacion_id')->unique()->count();

        $diasGrafico = $periodo === 'total' ? 30 : min((int) $periodo, 30);
        $observabilidad = $this->observabilidad($desde);

        return [
            'periodo' => $periodo,
            'periodo_label' => self::PERIODOS[$periodo],
            'total_conversaciones' => $totalConversaciones,
            'total_mensajes' => $totalMensajes,
            'usuarios_identificados' => $conversaciones->pluck('user_id')->filter()->unique()->count(),
            'conversaciones_visitantes' => $conversaciones->whereNull('user_id')->count(),
            'reservas_creadas' => $conversiones->pluck('reserva_id')->unique()->count(),
            'conversaciones_convertidas' => $conversacionesConvertidas,
            'tasa_conversion' => $this->porcentaje($conversacionesConvertidas, $totalConversaciones),
            'consultas_bloqueadas' => $bloqueadas,
            'tasa_bloqueo' => $this->porcentaje($bloqueadas, $totalMensajes),
            'consultas_no_resueltas' => $noResueltas,
            'tasa_no_resueltas' => $this->porcentaje($noResueltas, $totalMensajes),
            'mensajes_por_conversacion' => $totalConversaciones > 0
                ? round($totalMensajes / $totalConversaciones, 1)
                : 0.0,
            'intenciones' => $this->intenciones($mensajesUsuario, $totalMensajes),
            'actividad' => $this->actividadPorDia($diasGrafico),
            'observabilidad' => $observabilidad,
        ];
    }

    private function mensajesUsuario(?Carbon $desde): Builder
    {
        return MensajeChatbot::query()
            ->where('emisor', MensajeChatbot::EMISOR_USUARIO)
            ->when($desde, fn (Builder $query) => $query->where('created_at', '>=', $desde));
    }

    /**
     * @return Collection<int, array{conversation_id: int, reserva_id: int}>
     */
    private function conversiones(Collection $conversaciones, ?Carbon $desde): Collection
    {
        return $conversaciones->flatMap(function (Conversacion $conversacion) use ($desde): array {
            $registros = $conversacion->contextoDe(
                ReservaConversacionalService::CONTEXTO_RESERVAS_CREADAS,
                []
            );

            if (! is_array($registros)) {
                return [];
            }

            return collect($registros)
                ->filter(function (mixed $registro) use ($desde): bool {
                    if (! is_array($registro) || ! is_numeric($registro['reserva_id'] ?? null)) {
                        return false;
                    }

                    if (! $desde) {
                        return true;
                    }

                    try {
                        return Carbon::parse($registro['created_at'] ?? null)->greaterThanOrEqualTo($desde);
                    } catch (Throwable) {
                        return false;
                    }
                })
                ->map(fn (array $registro) => [
                    'conversation_id' => $conversacion->id,
                    'reserva_id' => (int) $registro['reserva_id'],
                ])
                ->all();
        })->unique(fn (array $conversion) => $conversion['conversation_id'].'-'.$conversion['reserva_id'])->values();
    }

    /**
     * @return Collection<int, array{clave: string, etiqueta: string, total: int, porcentaje: float}>
     */
    private function intenciones(Builder $mensajesUsuario, int $totalMensajes): Collection
    {
        return (clone $mensajesUsuario)
            ->selectRaw('intencion, COUNT(*) as total')
            ->whereNotNull('intencion')
            ->groupBy('intencion')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn (MensajeChatbot $mensaje) => [
                'clave' => $mensaje->intencion,
                'etiqueta' => $this->etiquetaIntencion($mensaje->intencion),
                'total' => (int) $mensaje->total,
                'porcentaje' => $this->porcentaje((int) $mensaje->total, $totalMensajes),
            ]);
    }

    /**
     * @return array<string, int>
     */
    private function actividadPorDia(int $dias): array
    {
        $desde = now()->subDays($dias - 1)->startOfDay();
        $conteos = MensajeChatbot::query()
            ->where('emisor', MensajeChatbot::EMISOR_USUARIO)
            ->where('created_at', '>=', $desde)
            ->selectRaw('DATE(created_at) as fecha, COUNT(*) as total')
            ->groupBy('fecha')
            ->pluck('total', 'fecha');

        $resultado = [];
        for ($dia = 0; $dia < $dias; $dia++) {
            $fecha = $desde->copy()->addDays($dia)->toDateString();
            $resultado[$fecha] = (int) ($conteos[$fecha] ?? 0);
        }

        return $resultado;
    }

    /** @return array<string, float|int> */
    private function observabilidad(?Carbon $desde): array
    {
        $consulta = ChatbotEjecucion::query()
            ->when($desde, fn (Builder $query) => $query->where('created_at', '>=', $desde));
        $total = (clone $consulta)->count();
        $intentosExternos = (clone $consulta)->where('external_attempted', true)->count();
        $usoExterno = (clone $consulta)->where('external_used', true)->count();
        $fallbacks = (clone $consulta)->where('fallback_local', true)->count();
        $erroresExternos = (clone $consulta)
            ->where('external_attempted', true)
            ->where('external_used', false)
            ->count();
        $latencia = (clone $consulta)
            ->whereNotNull('external_latency_ms')
            ->avg('external_latency_ms');

        return [
            'total' => $total,
            'intentos_externos' => $intentosExternos,
            'uso_externo' => $usoExterno,
            'fallbacks' => $fallbacks,
            'errores_externos' => $erroresExternos,
            'tasa_uso_externo' => $this->porcentaje($usoExterno, $total),
            'latencia_promedio_ms' => $latencia === null ? 0 : (int) round((float) $latencia),
        ];
    }

    private function etiquetaIntencion(string $intencion): string
    {
        return [
            DetectorIntencion::SALUDO => 'Saludos',
            DetectorIntencion::DESPEDIDA => 'Despedidas',
            DetectorIntencion::AYUDA => 'Ayuda',
            DetectorIntencion::CONTACTO => 'Contacto',
            DetectorIntencion::CONSULTAR_HORARIOS => 'Horarios',
            DetectorIntencion::CONSULTAR_PRECIOS => 'Precios',
            DetectorIntencion::CONSULTAR_DISPONIBILIDAD => 'Disponibilidad',
            DetectorIntencion::HACER_RESERVA => 'Reservas',
            DetectorIntencion::CONSULTAR_ESTADO_RESERVA => 'Estado de reservas',
            DetectorIntencion::MODIFICAR_RESERVA => 'Modificar reservas',
            DetectorIntencion::CANCELAR_RESERVA => 'Cancelar reservas',
            DetectorIntencion::NO_DETECTADA => 'No resueltas',
            DetectorIntencion::SOLICITUD_BLOQUEADA => 'Bloqueadas',
        ][$intencion] ?? str($intencion)->replace('_', ' ')->title()->toString();
    }

    private function porcentaje(int $cantidad, int $total): float
    {
        return $total > 0 ? round(($cantidad / $total) * 100, 1) : 0.0;
    }
}
