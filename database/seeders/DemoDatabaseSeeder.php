<?php

namespace Database\Seeders;

use App\Models\Cancha;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoDatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Carga un escenario demostrativo exclusivamente sobre una base sin datos operativos.
     */
    public function run(): void
    {
        if (User::query()->exists() || Cancha::query()->exists() || Reserva::query()->exists()) {
            throw new RuntimeException(
                'DemoDatabaseSeeder solo puede ejecutarse sobre una base vacia para no alterar datos existentes.'
            );
        }

        $this->call([
            UserSeeder::class,
            CanchaSeeder::class,
            BalonSeeder::class,
            ReservaSeeder::class,
        ]);
    }
}
