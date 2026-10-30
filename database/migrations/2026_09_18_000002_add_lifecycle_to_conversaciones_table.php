<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversaciones', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('contexto');
            $table->timestamp('last_activity_at')->nullable()->after('status');
            $table->timestamp('context_expires_at')->nullable()->after('last_activity_at');
            $table->timestamp('closed_at')->nullable()->after('context_expires_at');
            $table->index(['user_id', 'status', 'last_activity_at']);
        });
    }

    public function down(): void
    {
        Schema::table('conversaciones', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status', 'last_activity_at']);
            $table->dropColumn([
                'status',
                'last_activity_at',
                'context_expires_at',
                'closed_at',
            ]);
        });
    }
};
