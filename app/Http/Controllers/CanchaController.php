<?php

namespace App\Http\Controllers;

use App\Models\Ajuste;
use App\Models\Balon;
use App\Models\Cancha;
use Illuminate\View\View;

class CanchaController extends Controller
{
    /**
     * Catálogo público de canchas disponibles.
     */
    public function index(): View
    {
        $canchas = Cancha::disponibles()
            ->withCount('horarios')
            ->orderBy('nombre')
            ->get();

        $balones = Balon::orderBy('tipo')->orderBy('marca')->orderBy('nombre')->get();
        $balonPremium = Balon::premium();
        $version = Balon::version();

        return view('canchas.index', compact('canchas', 'balones', 'balonPremium', 'version'));
    }

    /**
     * Detalle público de una cancha.
     */
    public function show(Cancha $cancha): View
    {
        $balones = Balon::orderBy('tipo')->orderBy('marca')->orderBy('nombre')->get();
        $balonPremium = Balon::premium();
        $version = Balon::version();
        $horarioAtencion = Ajuste::horarioAtencion();

        return view('canchas.show', compact('cancha', 'balones', 'balonPremium', 'version', 'horarioAtencion'));
    }
}
