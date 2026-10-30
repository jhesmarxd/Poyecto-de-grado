<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones_reserva', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anticipacion_maxima_dias')->default(14);
            $table->boolean('permitir_hoy')->default(true);
            $table->boolean('permitir_futuras')->default(true);
            $table->json('duraciones_permitidas');
            $table->unsignedSmallInteger('minutos_franja')->default(30);
            $table->string('zona_horaria', 64)->default('America/La_Paz');
            $table->timestamps();
        });

        Schema::create('horarios_semanales', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('dia_semana')->unique();
            $table->boolean('abierto')->default(true);
            $table->time('hora_apertura')->nullable();
            $table->time('hora_cierre')->nullable();
            $table->timestamps();
        });

        Schema::create('cambios_configuracion_reserva', function (Blueprint $table) {
            $table->id();
            $table->foreignId('administrador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 40);
            $table->json('valor_anterior')->nullable();
            $table->json('valor_nuevo');
            $table->string('motivo')->nullable();
            $table->string('estado', 30)->default('pendiente_revision');
            $table->timestamp('aplicado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('excepciones_reserva', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cancha_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('cambio_configuracion_id')->nullable()
                ->constrained('cambios_configuracion_reserva')->nullOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha');
            $table->string('tipo', 40);
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->string('motivo')->nullable();
            $table->text('mensaje_cliente')->nullable();
            $table->string('estado', 25)->default('borrador');
            $table->timestamp('aplicado_at')->nullable();
            $table->timestamps();
            $table->index(['fecha', 'estado']);
            $table->index(['cancha_id', 'fecha', 'estado']);
        });

        Schema::create('reservas_afectadas_configuracion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cambio_configuracion_id')
                ->constrained('cambios_configuracion_reserva')->cascadeOnDelete();
            $table->foreignId('excepcion_reserva_id')->nullable()
                ->constrained('excepciones_reserva')->nullOnDelete();
            $table->foreignId('reserva_id')->constrained()->cascadeOnDelete();
            $table->string('estado_anterior', 40);
            $table->string('estado_nuevo', 40)->nullable();
            $table->text('mensaje')->nullable();
            $table->string('estado_notificacion', 25)->default('pendiente');
            $table->timestamp('notificado_at')->nullable();
            $table->timestamp('resuelto_at')->nullable();
            $table->timestamps();
            $table->unique(['cambio_configuracion_id', 'reserva_id'], 'cambio_reserva_afectada_unique');
        });

        Schema::table('reservas', function (Blueprint $table) {
            $table->foreignId('reprogramada_desde_id')->nullable()->after('id')
                ->constrained('reservas')->nullOnDelete();
        });

        $ahora = now();
        DB::table('configuraciones_reserva')->insert([
            'anticipacion_maxima_dias' => 14,
            'permitir_hoy' => true,
            'permitir_futuras' => true,
            'duraciones_permitidas' => json_encode([60, 90, 120]),
            'minutos_franja' => 30,
            'zona_horaria' => 'America/La_Paz',
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);

        $ajustes = Schema::hasTable('ajustes')
            ? DB::table('ajustes')->whereIn('clave', [
                'horario_atencion_dia_inicio', 'horario_atencion_dia_fin',
                'horario_atencion_hora_inicio', 'horario_atencion_hora_fin',
            ])->pluck('valor', 'clave')
            : collect();
        // El horario informativo anterior se convierte en la configuración
        // semanal inicial. A partir de aquí las pantallas públicas lo leen de
        // horarios_semanales y los ajustes antiguos quedan sólo como espejo de
        // compatibilidad.
        $diaInicio = (int) ($ajustes['horario_atencion_dia_inicio'] ?? 1);
        $diaFin = (int) ($ajustes['horario_atencion_dia_fin'] ?? 6);
        $horaInicio = (string) ($ajustes['horario_atencion_hora_inicio'] ?? '08:00');
        $horaFin = (string) ($ajustes['horario_atencion_hora_fin'] ?? '22:00');

        foreach (range(1, 7) as $dia) {
            $abierto = $dia >= $diaInicio && $dia <= $diaFin;
            DB::table('horarios_semanales')->insert([
                'dia_semana' => $dia,
                'abierto' => $abierto,
                'hora_apertura' => $abierto ? $horaInicio : null,
                'hora_cierre' => $abierto ? $horaFin : null,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reprogramada_desde_id');
        });
        Schema::dropIfExists('reservas_afectadas_configuracion');
        Schema::dropIfExists('excepciones_reserva');
        Schema::dropIfExists('cambios_configuracion_reserva');
        Schema::dropIfExists('horarios_semanales');
        Schema::dropIfExists('configuraciones_reserva');
    }
};
