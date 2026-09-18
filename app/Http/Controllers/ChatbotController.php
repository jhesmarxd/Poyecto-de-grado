<?php

namespace App\Http\Controllers;

use App\Models\Conversacion;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    /**
     * Recibe el mensaje del usuario y devuelve la respuesta del bot.
     */
    public function mensaje(Request $request, ChatbotService $bot): JsonResponse
    {
        $data = $request->validate([
            'mensaje' => ['required', 'string', 'max:500'],
        ]);

        $conversacion = $this->obtenerConversacion($request);

        $resultado = $bot->responder($data['mensaje'], $conversacion, $request->user());

        return response()->json([
            'ok' => true,
            'respuesta' => $resultado['respuesta'],
            'intencion' => $resultado['intencion'],
            'componentes' => $resultado['componentes'] ?? [],
        ]);
    }

    /**
     * Busca o crea la conversación actual.
     *
     * - Si hay usuario autenticado, reutiliza su última conversación activa
     *   (para mantener el contexto entre visitas).
     * - Si es visitante, usa la conversación guardada en la sesión.
     */
    private function obtenerConversacion(Request $request): Conversacion
    {
        $conversacionId = $request->session()->get('conversacion_id');
        $conversacion = $conversacionId ? Conversacion::find($conversacionId) : null;

        // Una conversación autenticada nunca se reutiliza para otro usuario ni
        // para un visitante que cerró sesión en el mismo navegador.
        if ($conversacion && $conversacion->user_id) {
            $usuarioActualId = $request->user()?->id;
            if (! $usuarioActualId || $conversacion->user_id !== $usuarioActualId) {
                $conversacion = null;
            }
        }

        if ($conversacion && $request->user() && ! $conversacion->user_id) {
            $conversacion->update(['user_id' => $request->user()->id]);
        }

        if (! $conversacion && $request->user()) {
            $conversacion = Conversacion::where('user_id', $request->user()->id)
                ->latest('updated_at')
                ->first();
        }

        if (! $conversacion) {
            $conversacion = Conversacion::create([
                'user_id' => $request->user()?->id,
                'contexto' => [],
            ]);
        }

        $request->session()->put('conversacion_id', $conversacion->id);

        return $conversacion;
    }
}
