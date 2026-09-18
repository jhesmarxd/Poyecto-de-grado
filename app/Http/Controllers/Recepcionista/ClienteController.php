<?php

namespace App\Http\Controllers\Recepcionista;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteController extends Controller
{
    /**
     * Busca clientes por nombre, email o teléfono.
     */
    public function buscar(Request $request): View
    {
        $termino = $request->input('q');

        $clientes = User::where('rol', 'cliente')
            ->when($termino, function ($query, $termino) {
                $query->where(function ($q) use ($termino) {
                    $q->where('name', 'like', "%{$termino}%")
                      ->orWhere('email', 'like', "%{$termino}%")
                      ->orWhere('telefono', 'like', "%{$termino}%");
                });
            })
            ->orderBy('name')
            ->paginate(20);

        return view('recepcionista.clientes.buscar', compact('clientes', 'termino'));
    }

    /**
     * API endpoint para autocomplete de clientes.
     */
    public function autocomplete(Request $request)
    {
        $termino = $request->input('q', '');

        if (strlen($termino) < 2) {
            return response()->json([]);
        }

        $clientes = User::where('rol', 'cliente')
            ->where(function ($q) use ($termino) {
                $q->where('name', 'like', "%{$termino}%")
                  ->orWhere('email', 'like', "%{$termino}%")
                  ->orWhere('telefono', 'like', "%{$termino}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'email', 'telefono']);

        return response()->json($clientes);
    }
}
