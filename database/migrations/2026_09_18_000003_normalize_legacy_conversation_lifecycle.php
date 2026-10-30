<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ttlMinutes = max(1, (int) config('chatbot.conversation.ttl_minutes', 30));
        $now = Carbon::now();

        DB::table('conversaciones')
            ->where('status', 'active')
            ->whereNull('last_activity_at')
            ->orderBy('id')
            ->chunkById(100, function ($conversaciones) use ($ttlMinutes, $now): void {
                foreach ($conversaciones as $conversacion) {
                    $lastActivity = $this->legacyActivityAt(
                        $conversacion->updated_at ?? null,
                        $conversacion->created_at ?? null,
                        $now,
                        $ttlMinutes,
                    );
                    $contextExpiresAt = $lastActivity->copy()->addMinutes($ttlMinutes);
                    $values = [
                        'last_activity_at' => $lastActivity,
                        'context_expires_at' => $contextExpiresAt,
                    ];

                    if ($contextExpiresAt->lessThanOrEqualTo($now)) {
                        $values['status'] = 'expired';
                    }

                    DB::table('conversaciones')
                        ->where('id', $conversacion->id)
                        ->where('status', 'active')
                        ->whereNull('last_activity_at')
                        ->update($values);
                }
            }, 'id');
    }

    public function down(): void
    {
        // La normalización no se revierte para no reactivar conversaciones vencidas.
    }

    private function legacyActivityAt(
        mixed $updatedAt,
        mixed $createdAt,
        Carbon $now,
        int $ttlMinutes,
    ): Carbon {
        foreach ([$updatedAt, $createdAt] as $candidate) {
            if ($candidate === null) {
                continue;
            }

            try {
                return Carbon::parse($candidate);
            } catch (Throwable) {
                // Se prueba el siguiente timestamp disponible.
            }
        }

        return $now->copy()->subMinutes($ttlMinutes + 1);
    }
};
