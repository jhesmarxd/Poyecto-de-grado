<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_calendar_events', function (Blueprint $table) {
            $table->unique(['calendar_id', 'google_event_id'], 'google_calendar_event_identity');
        });
    }

    public function down(): void
    {
        Schema::table('google_calendar_events', function (Blueprint $table) {
            $table->dropUnique('google_calendar_event_identity');
        });
    }
};
