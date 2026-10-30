<?php

namespace App\Services\Chatbot;

use App\Models\Conversacion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ConversationLifecycleService
{
    public function obtenerOCrearActiva(?User $usuario, mixed $conversacionId = null): Conversacion
    {
        return DB::transaction(function () use ($usuario, $conversacionId): Conversacion {
            $userId = $this->bloquearUsuario($usuario);
            $conversacion = $this->buscarPorSesion($conversacionId, $userId);

            if ($conversacion && $this->estaExpirada($conversacion)) {
                $this->expirar($conversacion);
                $conversacion = null;
            }

            if ($userId) {
                $candidatas = Conversacion::query()
                    ->activas()
                    ->where('user_id', $userId)
                    ->orderByDesc('last_activity_at')
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($candidatas as $candidata) {
                    if ($this->estaExpirada($candidata)) {
                        $this->expirar($candidata);

                        continue;
                    }

                    if (! $conversacion) {
                        $conversacion = $candidata;

                        continue;
                    }

                    if ($candidata->isNot($conversacion)) {
                        $this->cerrar($candidata);
                    }
                }
            }

            return $conversacion ?? $this->crearActiva($userId);
        });
    }

    public function iniciarNueva(?User $usuario, mixed $conversacionId = null): Conversacion
    {
        return DB::transaction(function () use ($usuario, $conversacionId): Conversacion {
            $userId = $this->bloquearUsuario($usuario);
            $cerradas = [];
            $actual = $this->buscarPorSesion($conversacionId, $userId);

            if ($actual) {
                $this->cerrar($actual);
                $cerradas[] = $actual->id;
            }

            if ($userId) {
                Conversacion::query()
                    ->activas()
                    ->where('user_id', $userId)
                    ->when($cerradas, fn ($query) => $query->whereNotIn('id', $cerradas))
                    ->lockForUpdate()
                    ->get()
                    ->each(fn (Conversacion $conversacion) => $this->cerrar($conversacion));
            }

            return $this->crearActiva($userId);
        });
    }

    public function actualizarActividad(Conversacion $conversacion): void
    {
        if ($conversacion->status !== Conversacion::STATUS_ACTIVE) {
            return;
        }

        $this->limpiarContextoTransitorioVencido($conversacion);
        $ahora = now();
        $conversacion->update([
            'last_activity_at' => $ahora,
            'context_expires_at' => $ahora->copy()->addMinutes($this->ttlMinutes()),
        ]);
    }

    public function cerrar(Conversacion $conversacion): void
    {
        if ($conversacion->status !== Conversacion::STATUS_ACTIVE) {
            return;
        }

        $this->limpiarBorrador($conversacion);
        $ahora = now();
        $conversacion->update([
            'status' => Conversacion::STATUS_CLOSED,
            'closed_at' => $ahora,
            'context_expires_at' => $ahora,
        ]);
    }

    public function expirar(Conversacion $conversacion): void
    {
        if ($conversacion->status !== Conversacion::STATUS_ACTIVE) {
            return;
        }

        $this->limpiarBorrador($conversacion);
        $conversacion->update([
            'status' => Conversacion::STATUS_EXPIRED,
            'context_expires_at' => now(),
            'closed_at' => null,
        ]);
    }

    public function estaExpirada(Conversacion $conversacion): bool
    {
        if ($conversacion->status !== Conversacion::STATUS_ACTIVE) {
            return true;
        }

        $ultimaActividad = $conversacion->last_activity_at
            ?? $conversacion->updated_at
            ?? $conversacion->created_at;

        if (! $ultimaActividad) {
            return true;
        }

        try {
            return Carbon::parse($ultimaActividad)
                ->addMinutes($this->ttlMinutes())
                ->lessThanOrEqualTo(now());
        } catch (\Throwable) {
            return true;
        }
    }

    public function limpiarContextoTransitorioVencido(Conversacion $conversacion): bool
    {
        $borrador = $conversacion->contextoDe(ReservaConversacionalService::CONTEXTO_BORRADOR);
        if (! is_array($borrador) || ! $this->borradorVencido($borrador)) {
            return false;
        }

        $this->limpiarBorrador($conversacion);

        return true;
    }

    private function buscarPorSesion(mixed $conversacionId, ?int $userId): ?Conversacion
    {
        $id = filter_var($conversacionId, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($id === false) {
            return null;
        }

        $conversacion = Conversacion::query()->lockForUpdate()->find($id);
        if (! $conversacion || $conversacion->status !== Conversacion::STATUS_ACTIVE) {
            return null;
        }

        $propietario = $conversacion->user_id === null ? null : (int) $conversacion->user_id;

        return $propietario === $userId ? $conversacion : null;
    }

    private function bloquearUsuario(?User $usuario): ?int
    {
        if (! $usuario) {
            return null;
        }

        $usuarioBloqueado = User::query()
            ->select('id')
            ->whereKey($usuario->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        return (int) $usuarioBloqueado->getKey();
    }

    private function crearActiva(?int $userId): Conversacion
    {
        $ahora = now();

        return Conversacion::create([
            'user_id' => $userId,
            'contexto' => [],
            'status' => Conversacion::STATUS_ACTIVE,
            'last_activity_at' => $ahora,
            'context_expires_at' => $ahora->copy()->addMinutes($this->ttlMinutes()),
            'closed_at' => null,
        ]);
    }

    private function limpiarBorrador(Conversacion $conversacion): void
    {
        $contexto = $conversacion->contexto ?? [];
        unset($contexto[ReservaConversacionalService::CONTEXTO_BORRADOR]);
        if (($contexto['intencion_pendiente'] ?? null) === DetectorIntencion::HACER_RESERVA) {
            unset($contexto['intencion_pendiente']);
        }

        if ($contexto !== ($conversacion->contexto ?? [])) {
            $conversacion->update(['contexto' => $contexto]);
        }
    }

    /** @param array<string, mixed> $borrador */
    private function borradorVencido(array $borrador): bool
    {
        try {
            return empty($borrador['expires_at'])
                || Carbon::parse($borrador['expires_at'])->isPast();
        } catch (\Throwable) {
            return true;
        }
    }

    private function ttlMinutes(): int
    {
        return max(1, (int) config('chatbot.conversation.ttl_minutes', 30));
    }
}
