<?php

namespace App\Http\Controllers;

use App\Services\Chatbot\ConversationLifecycleService;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    /**
     * Recibe el mensaje del usuario y devuelve la respuesta del bot.
     */
    public function mensaje(
        Request $request,
        ChatbotService $bot,
        ConversationLifecycleService $lifecycle,
    ): JsonResponse {
        $data = $request->validate([
            'mensaje' => ['required', 'string', 'max:500'],
        ]);

        $conversacion = $lifecycle->obtenerOCrearActiva(
            $request->user(),
            $request->session()->get('conversacion_id'),
        );
        $request->session()->put('conversacion_id', $conversacion->id);

        $resultado = $bot->responder($data['mensaje'], $conversacion, $request->user());
        $lifecycle->actualizarActividad($conversacion->fresh());

        $respuesta = [
            'ok' => true,
            'respuesta' => $resultado['respuesta'],
            'intencion' => $resultado['intencion'],
            'componentes' => $resultado['componentes'] ?? [],
        ];
        foreach (['estado', 'resultado', 'dato_pendiente'] as $campoOpcional) {
            if (array_key_exists($campoOpcional, $resultado) && $resultado[$campoOpcional] !== null) {
                $respuesta[$campoOpcional] = $resultado[$campoOpcional];
            }
        }

        return response()->json($respuesta);
    }

    /**
     * Cierra la conversación activa y crea otra con contexto limpio.
     */
    public function nuevaConversacion(
        Request $request,
        ConversationLifecycleService $lifecycle,
    ): JsonResponse {
        $conversacion = $lifecycle->iniciarNueva(
            $request->user(),
            $request->session()->get('conversacion_id'),
        );

        $request->session()->put('conversacion_id', $conversacion->id);

        return response()->json([
            'ok' => true,
            'conversacion_id' => $conversacion->id,
            'status' => $conversacion->status,
        ]);
    }
}
