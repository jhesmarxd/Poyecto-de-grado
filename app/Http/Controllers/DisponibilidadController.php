<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use App\Services\DisponibilidadService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisponibilidadController extends Controller
{
    public function __construct(
        private readonly DisponibilidadService $disponibilidadService
    ) {}

    /**
     * Consulta de disponibilidad: elige una cancha y una fecha
     * y muestra las franjas horarias libres.
     */
    public function index(Request $request): View
    {
        $canchas = $this->disponibilidadService->canchasDisponibles();

        $canchaId = $request->integer('cancha_id');
        $fecha = $request->input('fecha');

        $cancha = $canchas->firstWhere('id', $canchaId);
        $horariosLibres = $cancha && $fecha
            ? $this->disponibilidadService->horariosLibres($cancha, $fecha)
            : collect();

        return view('disponibilidad.index', compact('canchas', 'cancha', 'fecha', 'horariosLibres'));
    }
}
