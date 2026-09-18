<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAjusteRequest;
use App\Models\Ajuste;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AjusteController extends Controller
{
    /**
     * Paletas sugeridas: [color principal, color de acento, color oscuro].
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    public const PALETAS = [
        'Verde deportivo' => ['#198754', '#ffc107', '#212529'],
        'Azul océano' => ['#0d6efd', '#20c997', '#0f172a'],
        'Violeta noche' => ['#6f42c1', '#f472b6', '#1e1b4b'],
        'Naranja energía' => ['#fd7e14', '#198754', '#292524'],
        'Rojo pasión' => ['#dc3545', '#ffc107', '#2d1b1b'],
        'Gris minimalista' => ['#475569', '#0ea5e9', '#1e293b'],
    ];

    /**
     * Formulario para personalizar la presentación del sitio.
     */
    public function index(): View
    {
        return view('admin.ajustes.index', [
            'colores' => Ajuste::colores(),
            'horarioAtencion' => Ajuste::horarioAtencion(),
            'paletas' => self::PALETAS,
        ]);
    }

    /**
     * Guarda la presentación configurada por el administrador.
     */
    public function update(UpdateAjusteRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        DB::transaction(function () use ($datos): void {
            Ajuste::guardarColores([
                'color_primario' => $datos['color_primario'],
                'color_acento' => $datos['color_acento'],
                'color_oscuro' => $datos['color_oscuro'],
            ]);

            Ajuste::guardarHorarioAtencion([
                'horario_atencion_dia_inicio' => $datos['horario_atencion_dia_inicio'],
                'horario_atencion_dia_fin' => $datos['horario_atencion_dia_fin'],
                'horario_atencion_hora_inicio' => $datos['horario_atencion_hora_inicio'],
                'horario_atencion_hora_fin' => $datos['horario_atencion_hora_fin'],
            ]);
        });

        return redirect()->route('admin.ajustes.index')
            ->with('status', 'Ajustes actualizados correctamente.');
    }
}
