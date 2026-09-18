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
        Schema::create('balones', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['economico', 'premium'])->unique();
            $table->string('nombre');
            $table->string('imagen')->nullable();
            $table->decimal('precio_por_hora', 10, 2);
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balones');
    }
};