<?php

namespace Database\Seeders;

use App\Models\Cancha;
use Illuminate\Database\Seeder;

class CanchaSeeder extends Seeder
{
    /**
     * Crea las dos canchas de Wally y franjas de 30 minutos de lunes a sábado.
     * Idempotente: puede ejecutarse varias veces sin duplicar registros.
     */
    public function run(): void
    {
        $canchas = [
            [
                'nombre' => 'Cancha 1',
                'descripcion' => 'Cancha cerrada (techada). Ideal para jugar todo el día sin sol ni lluvia.',
                'ubicacion' => 'Complejo Wally · Zona Norte',
                'precio_por_hora' => 25.00,
                'estado' => Cancha::ESTADO_DISPONIBLE,
                'imagen' => 'cancha-1.jpeg',
                'color_malla' => 'Verde',
                'altura_malla' => '2 m',
                'tipo_piso' => 'Cemento pulido',
                'tipo_recinto' => 'cerrada',
            ],
            [
                'nombre' => 'Cancha 2',
                'descripcion' => 'Cancha cerrada (techada). Confort y seguridad en cada partido.',
                'ubicacion' => 'Complejo Wally · Zona Sur',
                'precio_por_hora' => 25.00,
                'estado' => Cancha::ESTADO_DISPONIBLE,
                'imagen' => 'cancha-2.jpeg',
                'color_malla' => 'Negra',
                'altura_malla' => '1.80 m',
                'tipo_piso' => 'Sintético',
                'tipo_recinto' => 'cerrada',
            ],
        ];

        foreach ($canchas as $datos) {
            $cancha = Cancha::updateOrCreate(
                ['nombre' => $datos['nombre']],
                $datos
            );

            if (! $cancha->estaDisponible()) {
                continue;
            }

            foreach (range(1, 6) as $dia) {
                for ($minuto = 8 * 60; $minuto < 22 * 60; $minuto += 30) {
                    $inicio = sprintf('%02d:%02d:00', intdiv($minuto, 60), $minuto % 60);
                    $finMinuto = $minuto + 30;
                    $fin = sprintf('%02d:%02d:00', intdiv($finMinuto, 60), $finMinuto % 60);
                    $cancha->horarios()->firstOrCreate(
                        ['dia_semana' => $dia, 'hora_inicio' => $inicio],
                        ['hora_fin' => $fin, 'disponible' => true]
                    );
                }
            }
        }
    }
}
