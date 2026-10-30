<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNull('rol')->update(['rol' => 'cliente']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE users MODIFY COLUMN rol ENUM('admin', 'cliente', 'recepcionista') NOT NULL DEFAULT 'cliente'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE users MODIFY COLUMN rol ENUM('admin', 'cliente', 'recepcionista') NULL DEFAULT 'cliente'"
            );
        }
    }
};
