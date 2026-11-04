<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use App\Services\DisponibilidadService;
use App\Services\PoliticaReservasService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DisponibilidadController extends Controller
{
    public function __construct(
        private readonly DisponibilidadService $disponibilidadService,
        private readonly PoliticaReservasService $politicaReservas
    ) {}

    /**
     * Consulta de disponibilidad: elige una cancha y una fecha
     * y muestra las franjas horarias libres.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $canchas = $this->disponibilidadService->canchasDisponibles();

        $canchaId = $request->integer('cancha_id');
        $fecha = $request->input('fecha');

        $cancha = $canchas->firstWhere('id', $canchaId);
        if ($request->boolean('consultar') && ! $request->user() && $cancha
            && $this->disponibilidadService->esFechaValida($fecha)
            && $fecha >= now()->toDateString()) {
            $request->session()->put('url.intended', route('disponibilidad.index', [
                'cancha_id' => $cancha->id,
                'fecha' => $fecha,
            ]));
            $request->session()->put('reserva_pendiente', $cancha->nombre);

            return redirect()->route('login');
        }

        $franjas = $cancha && $fecha
            ? $this->politicaReservas->franjasParaConsulta($cancha, $fecha)
            : collect();
        $duraciones = array_map('intval', $this->politicaReservas->configuracion()->duraciones_permitidas ?: []);

        return view('disponibilidad.index', compact('canchas', 'cancha', 'fecha', 'franjas', 'duraciones'));
    }
}
