<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convierte balones en un catálogo: cada balón es un registro propio
     * (varios económicos y varios premium) con marca, color y calidad.
     */
    public function up(): void
    {
        Schema::table('balones', function (Blueprint $table) {
            $table->dropUnique('balones_tipo_unique');

            $table->string('marca')->nullable()->after('tipo');
            $table->string('color')->nullable()->after('nombre');
            $table->string('calidad')->nullable()->after('color');
            $table->boolean('destacado')->default(false)->after('descripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('balones', function (Blueprint $table) {
            $table->dropColumn(['marca', 'color', 'calidad', 'destacado']);
            $table->unique('tipo');
        });
    }
};