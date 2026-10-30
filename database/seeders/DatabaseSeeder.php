<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command?->info(
            'No se cargaron datos de demostracion. Para una base vacia use DemoDatabaseSeeder de forma explicita.'
        );
    }
}
