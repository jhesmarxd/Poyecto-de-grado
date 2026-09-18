<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversacion;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversacionController extends Controller
{
    /**
     * Historial de conversaciones mantenidas con el chatbot.
     */
    public function index(Request $request): View
    {
        $conversaciones = Conversacion::query()
            ->with(['usuario'])
            ->withCount('mensajes')
            ->when($request->input('solo_usuarios'), fn ($q) => $q->whereNotNull('user_id'))
            ->latest('updated_at')
            ->get();

        return view('admin.chatbot.conversaciones', compact('conversaciones'));
    }

    /**
     * Detalle de una conversación (mensajes completos).
     */
    public function show(Conversacion $conversacion): View
    {
        $conversacion->load([
            'usuario',
            'mensajes' => fn ($q) => $q->with('ejecucion')->orderBy('created_at'),
        ]);

        return view('admin.chatbot.conversacion', compact('conversacion'));
    }
}
