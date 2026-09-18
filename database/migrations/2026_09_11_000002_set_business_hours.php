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
            'horario_atencion_dia_inicio' => '1',
            'horario_atencion_dia_fin' => '6',
            'horario_atencion_hora_inicio' => '08:00',
            'horario_atencion_hora_fin' => '22:00',
        ]);
    }

    public function down(): void
    {
        DB::table('ajustes')
            ->whereIn('clave', Ajuste::CLAVES_HORARIO_ATENCION)
            ->delete();

        Cache::forget(Ajuste::CACHE_HORARIO_ATENCION);
    }

    /**
     * @param  array<string, string>  $valores
     */
    private function guardar(array $valores): void
    {
        foreach ($valores as $clave => $valor) {
            DB::table('ajustes')->updateOrInsert(['clave' => $clave], ['valor' => $valor]);
        }

        Cache::forget(Ajuste::CACHE_HORARIO_ATENCION);
    }
};
