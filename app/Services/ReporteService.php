<?php

namespace App\Services;

use App\Models\Reserva;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteService
{
    /**
     * Calcula el rango de fechas del filtro (por defecto: este mes).
     *
     * @return array{0: string, 1: string}
     */
    public function rangoFechas(Request $request): array
    {
        $desde = $request->input('desde') ?: now()->startOfMonth()->toDateString();
        $hasta = $request->input('hasta') ?: now()->endOfMonth()->toDateString();

        $desde = Carbon::parse($desde)->toDateString();
        $hasta = Carbon::parse($hasta)->toDateString();

        if ($hasta < $desde) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [$desde, $hasta];
    }

    /**
     * Obtiene reservas con filtros para reportes.
     */
    public function reservasConFiltros(Request $request): Collection
    {
        [$desde, $hasta] = $this->rangoFechas($request);

        $query = Reserva::query()
            ->with(['usuario', 'cancha', 'horario', 'balon'])
            ->whereBetween('fecha', [$desde, $hasta]);

        if ($estado = $request->input('estado')) {
            $query->where('estado', $estado);
        }
        if ($canchaId = $request->integer('cancha_id')) {
            $query->where('cancha_id', $canchaId);
        }

        return $query->orderBy('fecha')->orderBy('id')->get();
    }

    /**
     * Calcula el resumen de un reporte.
     */
    public function calcularResumen($reservas): array
    {
        $reservasActivas = $reservas->where('estado', '!=', Reserva::ESTADO_CANCELADA);

        return [
            'total' => $reservas->count(),
            'ingresos' => $reservasActivas->sum('monto_total'),
            'activas' => $reservasActivas->count(),
        ];
    }

    /**
     * Exporta reservas a CSV.
     */
    public function exportarCSV(Request $request): StreamedResponse
    {
        $reservas = $this->reservasConFiltros($request);
        [$desde, $hasta] = $this->rangoFechas($request);

        $nombre = 'reporte_reservas_'.$desde.'_'.$hasta.'.csv';

        return response()->streamDownload(function () use ($reservas) {
            $salida = fopen('php://output', 'w');
            // BOM UTF-8 para que Excel reconozca los acentos
            fwrite($salida, "\xEF\xBB\xBF");

            fputcsv($salida, [
                'ID', 'Cliente', 'Teléfono', 'Cancha', 'Fecha', 'Hora inicio',
                'Hora fin', 'Balón', 'Monto (Bs.)', 'Estado', 'Observaciones',
            ]);

            foreach ($reservas as $r) {
                fputcsv($salida, [
                    $r->id,
                    $r->usuario->name,
                    $r->usuario->telefono ?? '',
                    $r->cancha->nombre,
                    $r->fecha->format('d/m/Y'),
                    $r->iniciaEn()->format('H:i'),
                    $r->finalizaEn()->format('H:i'),
                    $r->balonLabelCorta(),
                    number_format((float) $r->monto_total, 2),
                    $r->estadoLabel(),
                    $r->observaciones ?? '',
                ]);
            }

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv']);
    }
}
