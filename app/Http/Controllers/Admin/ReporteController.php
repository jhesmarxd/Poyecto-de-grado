<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cancha;
use App\Models\Reserva;
use App\Services\ReporteService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function __construct(
        private readonly ReporteService $reporteService
    ) {}

    /**
     * Reporte de reservas con filtros por rango de fechas,
     * cancha y estado.
     */
    public function index(Request $request): View
    {
        [$desde, $hasta] = $this->reporteService->rangoFechas($request);

        $reservas = $this->reporteService->reservasConFiltros($request);
        $canchas = Cancha::orderBy('nombre')->get();
        $resumen = $this->reporteService->calcularResumen($reservas);

        $estado = $request->input('estado');
        $canchaId = $request->integer('cancha_id');

        return view('admin.reportes.index', compact(
            'reservas',
            'canchas',
            'desde',
            'hasta',
            'estado',
            'canchaId',
            'resumen',
        ));
    }

    /**
     * Exporta el reporte filtrado a CSV (compatible con Excel).
     */
    public function exportar(Request $request): StreamedResponse
    {
        return $this->reporteService->exportarCSV($request);
    }
}
