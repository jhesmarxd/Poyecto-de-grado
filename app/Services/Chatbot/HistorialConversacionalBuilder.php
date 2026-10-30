<?php

namespace App\Services\Chatbot;

use App\Models\Conversacion;
use App\Models\MensajeChatbot;
use Illuminate\Support\Collection;

final class HistorialConversacionalBuilder
{
    public function __construct(
        private readonly PoliticaSeguridadChatbot $politicaSeguridad,
        private readonly ConversationLifecycleService $lifecycle,
    ) {}

    /**
     * @return array<int, array{role: string, content: string}>
     */
    public function construir(Conversacion $conversacion): array
    {
        if ($conversacion->status !== Conversacion::STATUS_ACTIVE
            || $this->lifecycle->estaExpirada($conversacion)) {
            return [];
        }

        $usuarios = $this->mensajesSeguros(
            $conversacion,
            MensajeChatbot::EMISOR_USUARIO,
            $this->limite('max_user_messages'),
        );
        $bot = $this->mensajesSeguros(
            $conversacion,
            MensajeChatbot::EMISOR_BOT,
            $this->limite('max_bot_messages'),
        );

        return $usuarios
            ->merge($bot)
            ->sortBy('id')
            ->values()
            ->map(fn (MensajeChatbot $mensaje): array => [
                'role' => $mensaje->emisor === MensajeChatbot::EMISOR_USUARIO
                    ? 'user'
                    : 'assistant',
                'content' => $mensaje->contenido,
            ])
            ->all();
    }

    /** @return Collection<int, MensajeChatbot> */
    private function mensajesSeguros(
        Conversacion $conversacion,
        string $emisor,
        int $limite,
    ): Collection {
        if ($limite === 0) {
            return collect();
        }

        return $conversacion->mensajes()
            ->where('emisor', $emisor)
            ->where(function ($query): void {
                $query->whereNull('intencion')
                    ->orWhere('intencion', '!=', DetectorIntencion::SOLICITUD_BLOQUEADA);
            })
            ->orderByDesc('id')
            ->get()
            ->filter(fn (MensajeChatbot $mensaje): bool => $this->contenidoSeguro($mensaje->contenido))
            ->take($limite);
    }

    private function contenidoSeguro(string $contenido): bool
    {
        if ($this->politicaSeguridad->evaluar($contenido)) {
            return false;
        }

        return preg_match(
            '/(?:AIza[0-9A-Za-z_-]+|\b(?:api[_ -]?key|access[_ -]?token|refresh[_ -]?token|password|secret)\s*[:=]\s*\S+|SQLSTATE\[|Stack trace:)/i',
            $contenido
        ) !== 1;
    }

    private function limite(string $clave): int
    {
        return max(0, min(50, (int) config("chatbot.history.{$clave}", 6)));
    }
}
