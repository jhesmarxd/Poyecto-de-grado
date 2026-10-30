<?php

namespace Database\Factories;

use App\Models\Cancha;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cancha>
 */
class CanchaFactory extends Factory
{
    protected $model = Cancha::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Cancha '.fake()->unique()->city(),
            'descripcion' => fake()->sentence(),
            'ubicacion' => 'Zona '.fake()->randomElement(['Norte', 'Central', 'Sur']),
            'precio_por_hora' => fake()->randomFloat(2, 30, 120),
            'estado' => Cancha::ESTADO_DISPONIBLE,
            'imagen' => null,
            'color_malla' => fake()->randomElement(['Verde', 'Negra', 'Blanca']),
            'altura_malla' => fake()->randomElement(['1.80 m', '2 m', '2.20 m']),
            'tipo_piso' => fake()->randomElement(['Cemento pulido', 'Sintético', 'Parquet']),
            'tipo_recinto' => 'cerrada',
        ];
    }
}
