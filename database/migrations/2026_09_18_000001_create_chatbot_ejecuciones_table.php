<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_ejecuciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mensaje_chatbot_id')
                ->unique()
                ->constrained('mensajes_chatbot')
                ->cascadeOnDelete();
            $table->foreignId('conversacion_id')
                ->constrained('conversaciones')
                ->cascadeOnDelete();
            $table->string('provider', 30)->nullable();
            $table->string('model', 120)->nullable();
            $table->string('external_status', 60);
            $table->boolean('external_attempted')->default(false);
            $table->boolean('external_used')->default(false);
            $table->boolean('fallback_local')->default(false);
            // external, local, draft, blocked o none.
            $table->string('engine_used', 20);
            $table->unsignedInteger('external_latency_ms')->nullable();
            $table->unsignedSmallInteger('external_http_status')->nullable();
            $table->string('external_error_code', 80)->nullable();
            $table->text('external_error_message')->nullable();
            $table->string('detected_intent', 60)->nullable();
            $table->json('structured_parameters')->nullable();
            $table->timestamps();

            $table->index(['conversacion_id', 'created_at']);
            $table->index(['engine_used', 'created_at']);
            $table->index(['external_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_ejecuciones');
    }
};
