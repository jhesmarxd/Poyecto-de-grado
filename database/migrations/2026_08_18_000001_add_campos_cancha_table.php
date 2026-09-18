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
        Schema::table('canchas', function (Blueprint $table) {
            $table->string('imagen')->nullable()->after('estado');
            $table->string('color_malla')->nullable()->after('imagen');
            $table->string('altura_malla')->nullable()->after('color_malla');
            $table->string('tipo_piso')->nullable()->after('altura_malla');
            $table->enum('tipo_recinto', ['cerrada', 'abierta'])->default('cerrada')->after('tipo_piso');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('canchas', function (Blueprint $table) {
            $table->dropColumn(['imagen', 'color_malla', 'altura_malla', 'tipo_piso', 'tipo_recinto']);
        });
    }
};