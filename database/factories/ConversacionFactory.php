<?php

namespace Database\Factories;

use App\Models\Conversacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversacion>
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
        ];
    }
}
