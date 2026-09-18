<?php

use App\Models\Ajuste;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->guardar([
            'color_primario' => '#008C95',
            'color_acento' => '#B8E000',
            'color_oscuro' => '#071A2B',
        ]);
    }

    public function down(): void
    {
        $this->guardar([
            'color_primario' => '#198754',
            'color_acento' => '#FFC107',
            'color_oscuro' => '#212529',
        ]);
    }

    /**
     * @param  array<string, string>  $colores
     */
    private function guardar(array $colores): void
    {
        foreach ($colores as $clave => $valor) {
            DB::table('ajustes')->updateOrInsert(['clave' => $clave], ['valor' => $valor]);
        }

        Cache::forget(Ajuste::CACHE_COLORES);
    }
};
