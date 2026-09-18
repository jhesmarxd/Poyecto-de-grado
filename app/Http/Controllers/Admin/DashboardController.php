<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EstadisticasService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Panel de administración con métricas y gráficos.
     */
    public function index(EstadisticasService $stats): View
    {
        $metricas = $stats->metricas();
        $reservasPorDia = $stats->reservasPorDia();
        $reservasPorEstado = $stats->reservasPorEstado();
        $canchasMasUsadas = $stats->canchasMasUsadas();

        return view('admin.dashboard', compact(
            'metricas',
            'reservasPorDia',
            'reservasPorEstado',
            'canchasMasUsadas',
        ));
    }
}
