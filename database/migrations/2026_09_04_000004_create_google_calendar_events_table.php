<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->nullable()->constrained('reservas')->nullOnDelete();
            $table->string('calendar_id');
            $table->string('google_event_id')->nullable();
            $table->string('google_event_url', 2048)->nullable();
            $table->string('etag')->nullable();
            $table->string('sync_status', 20)->default('pending');
            $table->string('payload_hash', 64)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique('reserva_id');
            $table->index(['sync_status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_calendar_events');
    }
};
