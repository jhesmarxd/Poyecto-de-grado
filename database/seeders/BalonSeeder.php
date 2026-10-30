<?php

namespace Database\Seeders;

use App\Models\Balon;
use Illuminate\Database\Seeder;

class BalonSeeder extends Seeder
{
    /**
     * Catálogo de balones disponibles (uno por foto subida a public/img/balones).
     * Idempotente: puede ejecutarse varias veces sin duplicar registros.
     */
    public function run(): void
    {
        $balones = [
            // --- Económicos (los presta la dueña) ---
            [
                'tipo' => Balon::TIPO_ECONOMICO,
                'marca' => 'Asatex',
                'nombre' => '1',
                'color' => 'Amarillo/verde',
                'calidad' => 'Estándar',
                'imagen' => 'Balon economico Asatex 1.png',
                'precio_por_hora' => 25.00,
                'destacado' => true,
                'descripcion' => 'Balón económico que la dueña presta sin costo extra. Ideal para partidos de práctica.',
            ],
            [
                'tipo' => Balon::TIPO_ECONOMICO,
                'marca' => 'Asatex',
                'nombre' => '2',
                'color' => 'Amarillo/verde',
                'calidad' => 'Estándar',
                'imagen' => 'Balon economico Asatex 2.png',
                'precio_por_hora' => 25.00,
                'destacado' => false,
                'descripcion' => 'Segundo balón Asatex disponible para prestar en tus partidos.',
            ],
            [
                'tipo' => Balon::TIPO_ECONOMICO,
                'marca' => 'Veloz',
                'nombre' => '1',
                'color' => 'Blanco/azul',
                'calidad' => 'Estándar',
                'imagen' => 'Balon economico Veloz 1.png',
                'precio_por_hora' => 25.00,
                'destacado' => false,
                'descripcion' => 'Balón Veloz económico, listo para el juego de todos los días.',
            ],
            [
                'tipo' => Balon::TIPO_ECONOMICO,
                'marca' => 'Veloz',
                'nombre' => '2',
                'color' => 'Blanco/azul',
                'calidad' => 'Estándar',
                'imagen' => 'Balon economico Veloz 2.png',
                'precio_por_hora' => 25.00,
                'destacado' => false,
                'descripcion' => 'Segunda unidad Veloz disponible para prestar.',
            ],

            // --- Premium (mejor calidad, con recargo) ---
            [
                'tipo' => Balon::TIPO_PREMIUM,
                'marca' => 'Mikasa',
                'nombre' => 'V200W',
                'color' => 'Blanco/azul',
                'calidad' => 'Profesional',
                'imagen' => 'Balon premiun Mikasa V200W Original.png',
                'precio_por_hora' => 30.00,
                'destacado' => true,
                'descripcion' => 'Balón oficial de competición. Agarre y rendimiento de primer nivel.',
            ],
            [
                'tipo' => Balon::TIPO_PREMIUM,
                'marca' => 'Mikasa',
                'nombre' => 'V330W',
                'color' => 'Blanco/azul',
                'calidad' => 'Alta',
                'imagen' => 'Balon premiun Mikasa V330W Orginal.png',
                'precio_por_hora' => 30.00,
                'destacado' => false,
                'descripcion' => 'Balón Mikasa de alta calidad, excelente para partidos exigentes.',
            ],
            [
                'tipo' => Balon::TIPO_PREMIUM,
                'marca' => 'Mikasa',
                'nombre' => 'V330W 2',
                'color' => 'Blanco/azul',
                'calidad' => 'Alta',
                'imagen' => 'Balon premiun Mikasa V330W Orginal.png',
                'precio_por_hora' => 30.00,
                'destacado' => false,
                'descripcion' => 'Segunda unidad Mikasa V330W disponible como balón premium.',
            ],
            [
                'tipo' => Balon::TIPO_PREMIUM,
                'marca' => 'Molten',
                'nombre' => '5500',
                'color' => 'Blanco/rojo',
                'calidad' => 'Profesional',
                'imagen' => 'Balon Premiun Molten 5500 Original.png',
                'precio_por_hora' => 30.00,
                'destacado' => false,
                'descripcion' => 'Balón Molten 5500 de competición, máximo control y durabilidad.',
            ],
        ];

        foreach ($balones as $datos) {
            Balon::updateOrCreate(
                ['tipo' => $datos['tipo'], 'marca' => $datos['marca'], 'nombre' => $datos['nombre']],
                $datos
            );
        }
    }
}
