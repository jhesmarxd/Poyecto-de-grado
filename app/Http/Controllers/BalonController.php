<?php

namespace App\Http\Controllers;

use App\Models\Balon;
use Illuminate\Http\JsonResponse;

class BalonController extends Controller
{
    /**
     * Devuelve la versión del catálogo de balones y todos sus datos actuales.
     * El cliente la consulta periódicamente para detectar cambios en tiempo
     * real y avisar que debe recargar la página.
     */
    public function version(): JsonResponse
    {
        $balones = Balon::orderBy('tipo')->orderBy('marca')->orderBy('nombre')->get();

        return response()->json([
            'version' => Balon::version(),
            'balones' => $balones->map(fn (Balon $balon) => [
                'id' => $balon->id,
                'tipo' => $balon->tipo,
                'marca' => $balon->marca,
                'nombre' => $balon->nombre,
                'color' => $balon->color,
                'calidad' => $balon->calidad,
                'precio_por_hora' => (float) $balon->precio_por_hora,
                'imagen' => $balon->imagenUrl(),
                'destacado' => (bool) $balon->destacado,
            ]),
        ]);
    }
}