<?php

namespace Database\Factories;

use App\Models\Cancha;
use App\Models\Horario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Horario>
 */
class HorarioFactory extends Factory
{
    protected $model = Horario::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cancha_id' => Cancha::factory(),
            'dia_semana' => Horario::LUNES,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
            'disponible' => true,
        ];
    }
}
