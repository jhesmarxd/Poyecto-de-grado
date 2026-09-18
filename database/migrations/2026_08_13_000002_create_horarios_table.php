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
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cancha_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana'); // 1 = Lunes ... 7 = Domingo
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->boolean('disponible')->default(true);
            $table->timestamps();

            // Un mismo horario de inicio no puede repetirse para la misma cancha y día
            $table->unique(['cancha_id', 'dia_semana', 'hora_inicio']);

            // La hora de fin debe ser posterior a la hora de inicio
            $table->index(['cancha_id', 'dia_semana']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};
