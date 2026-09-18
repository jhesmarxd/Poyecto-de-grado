<?php

namespace Database\Factories;

use App\Models\Cancha;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reserva>
 */
class ReservaFactory extends Factory
{
    protected $model = Reserva::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cancha = Cancha::factory()->create();
        $horario = Horario::factory()->create(['cancha_id' => $cancha->id]);

        return [
            'user_id' => User::factory(),
            'cancha_id' => $cancha->id,
            'horario_id' => $horario->id,
            'fecha' => now()->addDays(3)->toDateString(),
            'monto_total' => $cancha->precio_por_hora,
            'tipo_balon' => Reserva::BALON_PRESTADO,
            'estado' => Reserva::ESTADO_PENDIENTE,
            'observaciones' => null,
        ];
    }

    /**
     * Reserva cancelada.
     */
    public function cancelada(): static
    {
        return $this->state(fn () => ['estado' => Reserva::ESTADO_CANCELADA]);
    }

    /**
     * Reserva confirmada.
     */
    public function confirmada(): static
    {
        return $this->state(fn () => ['estado' => Reserva::ESTADO_CONFIRMADA]);
    }
}
