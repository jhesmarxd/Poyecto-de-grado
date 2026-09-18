<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            $table->date('vigente_desde')->nullable()->after('dia_semana');
            $table->date('vigente_hasta')->nullable()->after('vigente_desde');
            $table->index(['cancha_id', 'dia_semana', 'vigente_desde', 'vigente_hasta'], 'horarios_vigencia_idx');
        });

        Schema::table('reservas', function (Blueprint $table) {
            $table->time('hora_inicio_reserva')->nullable()->after('fecha');
            $table->time('hora_fin_reserva')->nullable()->after('hora_inicio_reserva');
            $table->unsignedSmallInteger('duracion_minutos')->nullable()->after('hora_fin_reserva');
            $table->index(
                ['cancha_id', 'fecha', 'hora_inicio_reserva', 'hora_fin_reserva'],
                'reservas_periodo_idx'
            );
        });

        DB::table('reservas')->orderBy('id')->eachById(function (object $reserva): void {
            $horario = DB::table('horarios')->find($reserva->horario_id);
            if (! $horario) {
                return;
            }

            $inicio = strtotime($horario->hora_inicio);
            $fin = strtotime($horario->hora_fin);
            if ($fin <= $inicio) {
                $fin += 86400;
            }

            DB::table('reservas')->where('id', $reserva->id)->update([
                'hora_inicio_reserva' => $horario->hora_inicio,
                'hora_fin_reserva' => $horario->hora_fin,
                'duracion_minutos' => (int) (($fin - $inicio) / 60),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropIndex('reservas_periodo_idx');
            $table->dropColumn(['hora_inicio_reserva', 'hora_fin_reserva', 'duracion_minutos']);
        });

        Schema::table('horarios', function (Blueprint $table) {
            $table->dropIndex('horarios_vigencia_idx');
            $table->dropColumn(['vigente_desde', 'vigente_hasta']);
        });
    }
};
