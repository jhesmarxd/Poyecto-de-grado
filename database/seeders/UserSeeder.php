<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Crea los usuarios iniciales del sistema con los 3 roles disponibles.
     */
    public function run(): void
    {
        // ── Administrador ──
        User::firstOrCreate(
            ['email' => 'admin@wally.com'],
            [
                'name' => 'Carlos Mendoza',
                'telefono' => '71234567',
                'password' => 'Admin123!',
                'rol' => User::ROL_ADMIN,
                'estado' => true,
            ]
        );

        // ── Recepcionista ──
        User::firstOrCreate(
            ['email' => 'recepcion@wally.com'],
            [
                'name' => 'María Elena Torres',
                'telefono' => '72345678',
                'password' => 'Recep123!',
                'rol' => User::ROL_RECEPCIONISTA,
                'estado' => true,
            ]
        );

        // ── Clientes con nombres reales ──
        $clientes = [
            ['name' => 'Juan Pablo García',     'email' => 'juan.garcia@email.com',    'telefono' => '73456789'],
            ['name' => 'Andrés Felipe López',   'email' => 'andres.lopez@email.com',   'telefono' => '74567890'],
            ['name' => 'Valentina Ruiz',        'email' => 'valentina.ruiz@email.com',  'telefono' => '75678901'],
            ['name' => 'Diego Fernando Morán',  'email' => 'diego.moran@email.com',     'telefono' => '76789012'],
            ['name' => 'Camila Andrea Rojas',   'email' => 'camila.rojas@email.com',    'telefono' => '77890123'],
            ['name' => 'Santiago Herrera',      'email' => 'santiago.herrera@email.com', 'telefono' => '78901234'],
            ['name' => 'Laura Sofía Vargas',    'email' => 'laura.vargas@email.com',     'telefono' => '79012345'],
            ['name' => 'Mateo Alejandro Díaz',  'email' => 'mateo.diaz@email.com',       'telefono' => '70123456'],
        ];

        foreach ($clientes as $cliente) {
            User::firstOrCreate(
                ['email' => $cliente['email']],
                [
                    'name' => $cliente['name'],
                    'telefono' => $cliente['telefono'],
                    'password' => 'Cliente123!',
                    'rol' => User::ROL_CLIENTE,
                    'estado' => true,
                ]
            );
        }
    }
}
