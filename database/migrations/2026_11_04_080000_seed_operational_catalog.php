<?php

use Database\Seeders\CatalogoProduccionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Render ejecuta migrate --force al iniciar. El catálogo se carga una
        // sola vez en PostgreSQL y el seeder permite repetirlo sin duplicados.
        if (DB::getDriverName() === 'pgsql' && ! app()->environment('testing')) {
            app(CatalogoProduccionSeeder::class)->run();
        }
    }

    public function down(): void
    {
        // Conservar canchas, balones y reservas si se revierte el código.
    }
};
