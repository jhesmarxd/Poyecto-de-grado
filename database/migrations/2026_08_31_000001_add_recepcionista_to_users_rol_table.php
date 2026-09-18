<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // MySQL: modificar el enum para incluir 'recepcionista'
            DB::statement("ALTER TABLE users MODIFY COLUMN rol ENUM('admin', 'cliente', 'recepcionista') DEFAULT 'cliente'");
        }
        if ($driver === 'sqlite') {
            // Laravel representa enum como un CHECK en SQLite.
            Schema::table('users', function (Blueprint $table) {
                $table->enum('rol', ['admin', 'cliente', 'recepcionista'])
                    ->default('cliente')
                    ->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN rol ENUM('admin', 'cliente') DEFAULT 'cliente'");
        }
    }
};
