<?php

namespace App\Services;

use App\Models\Cancha;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EstadisticasService
{
    /**
     * Métricas generales del dashboard.
     *
     * @return array<string, mixed>
     */
    public function metricas(): array
    {
        return [
            'total_reservas' => Reserva::count(),
            'reservas_activas' => Reserva::activas()->count(),
            'reservas_hoy' => Reserva::activas()->paraFecha(now()->toDateString())->count(),
            'ingresos_estimados' => $this->ingresosEstimados(),
            'total_clientes' => User::where('rol', User::ROL_CLIENTE)->count(),
            'total_canchas' => Cancha::count(),
            'canchas_disponibles' => Cancha::disponibles()->count(),
            'ocupacion_hoy' => $this->ocupacionHoy(),
            'reservas_pendientes' => Reserva::where('estado', Reserva::ESTADO_PENDIENTE)->count(),
            'reservas_confirmadas' => Reserva::where('estado', Reserva::ESTADO_CONFIRMADA)->count(),
            'reservas_canceladas' => Reserva::where('estado', Reserva::ESTADO_CANCELADA)->count(),
            'reservas_completadas' => Reserva::where('estado', Reserva::ESTADO_COMPLETADA)->count(),
        ];
    }

    /**
     * Cantidad de reservas activas (no canceladas).
     */
    public function reservasActivas(): int
    {
        return Reserva::activas()->count();
    }

    /**
     * Ingresos estimados: suma del monto de las reservas no canceladas.
     */
    public function ingresosEstimados(): float
    {
        return (float) Reserva::activas()->sum('monto_total');
    }

    /**
     * Reservas por día para los últimos $dias días (0 = sin reservas).
     *
     * @return array<string, int> clave Y-m-d
     */
    public function reservasPorDia(int $dias = 14): array
    {
        $desde = now()->subDays($dias - 1)->startOfDay();

        $conteos = Reserva::activas()
            ->where('fecha', '>=', $desde->toDateString())
            ->selectRaw('fecha, COUNT(*) as total')
            ->groupBy('fecha')
            ->pluck('total', 'fecha');

        $resultado = [];
        for ($i = 0; $i < $dias; $i++) {
            $fecha = $desde->copy()->addDays($i)->toDateString();
            $resultado[$fecha] = (int) ($conteos[$fecha] ?? 0);
        }

        return $resultado;
    }

    /**
     * Reservas por estado (etiqueta => cantidad).
     *
     * @return array<string, int>
     */
    public function reservasPorEstado(): array
    {
        return Reserva::query()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->mapWithKeys(fn ($total, $estado) => [
                Reserva::ESTADOS[$estado] ?? $estado => (int) $total,
            ])
            ->all();
    }

    /**
     * Canchas más usadas (con reservas activas), ordenadas de mayor a menor.
     *
     * @return Collection<int, array{nombre: string, total: int}>
     */
    public function canchasMasUsadas(int $limite = 5): Collection
    {
        return Reserva::activas()
            ->selectRaw('cancha_id, COUNT(*) as total')
            ->groupBy('cancha_id')
            ->orderByDesc('total')
            ->limit($limite)
            ->get()
            ->map(fn ($r) => [
                'nombre' => $r->cancha->nombre,
                'total' => (int) $r->total,
            ]);
    }

    /**
     * Ocupación de hoy: porcentaje de franjas habilitadas que ya están reservadas.
     */
    public function ocupacionHoy(): float
    {
        $fecha = now()->toDateString();
        $diaSemana = now()->dayOfWeekIso;

        $franjasHabilitadas = Horario::query()
            ->where('dia_semana', $diaSemana)
            ->where('disponible', true)
            ->count();

        if ($franjasHabilitadas === 0) {
            return 0.0;
        }

        $reservadas = Reserva::activas()
            ->paraFecha($fecha)
            ->count();

        return round(($reservadas / $franjasHabilitadas) * 100, 1);
    }
}
