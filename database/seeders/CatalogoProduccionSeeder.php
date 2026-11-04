<?php

namespace Database\Seeders;

use App\Models\ConfiguracionReserva;
use App\Models\HorarioSemanal;
use Illuminate\Database\Seeder;

/** Catálogo operativo sin usuarios ni reservas de demostración. */
class CatalogoProduccionSeeder extends Seeder
{
    public function run(): void
    {
        ConfiguracionReserva::query()->firstOrCreate([], [
            'anticipacion_maxima_dias' => 14,
            'permitir_hoy' => true,
            'permitir_futuras' => true,
            'duraciones_permitidas' => [60, 90, 120, 150, 180],
            'minutos_franja' => 30,
            'zona_horaria' => 'America/La_Paz',
        ]);
        ConfiguracionReserva::query()->first()?->update([
            'duraciones_permitidas' => [60, 90, 120, 150, 180],
            'minutos_franja' => 30,
        ]);

        foreach (range(1, 7) as $dia) {
            HorarioSemanal::query()->updateOrCreate(['dia_semana' => $dia], [
                'abierto' => $dia !== 7,
                'hora_apertura' => $dia === 7 ? null : '08:00:00',
                'hora_cierre' => $dia === 7 ? null : '22:00:00',
            ]);
        }

        $this->call([CanchaSeeder::class, BalonSeeder::class]);
    }
}
