<?php

namespace Database\Seeders;

use App\Models\Cancha;
use Illuminate\Database\Seeder;

class CanchaSeeder extends Seeder
{
    /**
     * Crea las dos canchas de Wally con sus horarios típicos.
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

        // Franjas típicas: lunes a viernes por la noche, fines de semana mañana y tarde
        $franjasSemana = [
            ['18:00', '19:00'], ['19:00', '20:00'], ['20:00', '21:00'], ['21:00', '22:00'],
        ];
        $franjasFinSemana = [
            ['09:00', '10:00'], ['10:00', '11:00'], ['11:00', '12:00'],
            ['16:00', '17:00'], ['17:00', '18:00'], ['18:00', '19:00'], ['19:00', '20:00'],
        ];

        foreach ($canchas as $datos) {
            $cancha = Cancha::updateOrCreate(
                ['nombre' => $datos['nombre']],
                $datos
            );

            if (! $cancha->estaDisponible()) {
                continue;
            }

            foreach (range(1, 5) as $dia) { // Lunes a Viernes
                foreach ($franjasSemana as [$inicio, $fin]) {
                    $cancha->horarios()->firstOrCreate(
                        ['dia_semana' => $dia, 'hora_inicio' => $inicio.':00'],
                        ['hora_fin' => $fin.':00', 'disponible' => true]
                    );
                }
            }

            foreach ([6, 7] as $dia) { // Sábado y Domingo
                foreach ($franjasFinSemana as [$inicio, $fin]) {
                    $cancha->horarios()->firstOrCreate(
                        ['dia_semana' => $dia, 'hora_inicio' => $inicio.':00'],
                        ['hora_fin' => $fin.':00', 'disponible' => true]
                    );
                }
            }
        }
    }
}
