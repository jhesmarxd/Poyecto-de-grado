<?php

namespace Database\Factories;

use App\Models\Conversacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversacion>
 */
class ConversacionFactory extends Factory
{
    protected $model = Conversacion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'contexto' => [],
            'status' => Conversacion::STATUS_ACTIVE,
            'last_activity_at' => now(),
            'context_expires_at' => now()->addMinutes((int) config('chatbot.conversation.ttl_minutes', 30)),
            'closed_at' => null,
        ];
    }
}
