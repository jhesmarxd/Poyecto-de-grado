<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cancha_id')->constrained()->cascadeOnDelete();
            $table->foreignId('horario_id')->constrained()->cascadeOnDelete();
            $table->date('fecha');
            $table->decimal('monto_total', 10, 2);
            $table->enum('estado', ['pendiente', 'confirmada', 'cancelada', 'completada', 'afectada_por_configuracion', 'cancelada_por_complejo', 'reprogramada'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['cancha_id', 'fecha']);
            $table->index(['user_id', 'fecha']);

            // Regla anti-duplicidad a nivel de base de datos:
            // una misma cancha no puede tener DOS reservas activas para la misma
            // fecha y horario. La columna generada 'activa' es NULL cuando la
            // reserva está cancelada, y como NULL nunca colisiona en un índice
            // único de MySQL/MariaDB, las reservas canceladas no bloquean que
            // se reserve de nuevo esa misma franja.
            $table->tinyInteger('activa')->storedAs("CASE WHEN estado = 'cancelada' THEN NULL ELSE 1 END");
            $table->unique(['cancha_id', 'fecha', 'horario_id', 'activa'], 'reservas_sin_duplicados');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
