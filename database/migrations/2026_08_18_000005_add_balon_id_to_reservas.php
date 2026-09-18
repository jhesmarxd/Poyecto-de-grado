<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La reserva guarda qué balón concreto eligió el cliente.
     * NULL = el cliente trae su propio balón.
     */
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->foreignId('balon_id')
                ->nullable()
                ->after('tipo_balon')
                ->constrained('balones')
                ->nullOnDelete();

            $table->index(['fecha', 'horario_id', 'balon_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropIndex(['fecha', 'horario_id', 'balon_id']);
            $table->dropForeign(['balon_id']);
            $table->dropColumn('balon_id');
        });
    }
};