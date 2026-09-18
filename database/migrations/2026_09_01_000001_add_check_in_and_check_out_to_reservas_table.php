<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->timestamp('check_in_at')->nullable()->after('observaciones');
            $table->timestamp('check_out_at')->nullable()->after('check_in_at');
        });

        // La versión anterior usaba "completada" al registrar la entrada.
        DB::table('reservas')
            ->where('estado', 'completada')
            ->whereNull('check_in_at')
            ->update(['check_in_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn(['check_in_at', 'check_out_at']);
        });
    }
};
