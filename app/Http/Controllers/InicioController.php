<?php

namespace App\Http\Controllers;

use App\Models\Balon;
use App\Models\Cancha;
use App\Services\EstadisticasService;
use Illuminate\View\View;

class InicioController extends Controller
{
    /**
     * Página de inicio con canchas destacadas y estadísticas rápidas.
     */
    public function index(EstadisticasService $stats): View
    {
        $canchasDestacadas = Cancha::disponibles()
            ->withCount(['horarios', 'reservas' => fn ($q) => $q->activas()])
            ->orderByDesc('reservas_count')
            ->orderBy('nombre')
            ->limit(3)
            ->get();

        $metricas = [
            'canchas' => Cancha::disponibles()->count(),
            'reservas' => $stats->reservasActivas(),
            'ingresos' => $stats->ingresosEstimados(),
        ];

        $balones = Balon::orderBy('tipo')->orderBy('marca')->orderBy('nombre')->get();
        $balonPremium = Balon::premium();
        $version = Balon::version();

        return view('welcome', compact('canchasDestacadas', 'metricas', 'balones', 'balonPremium', 'version'));
    }
}
