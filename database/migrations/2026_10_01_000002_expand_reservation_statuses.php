<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('reservas', 'reservas_sin_duplicados')) {
            Schema::table('reservas', function (Blueprint $table) {
                $table->dropUnique('reservas_sin_duplicados');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            if (Schema::hasColumn('reservas', 'activa')) {
                DB::statement('ALTER TABLE reservas DROP COLUMN activa');
            }
            $estado = DB::selectOne("SHOW COLUMNS FROM reservas LIKE 'estado'");
            if (! str_contains((string) ($estado->Type ?? ''), "'reprogramada'")) {
                DB::statement("ALTER TABLE reservas MODIFY estado ENUM('pendiente','confirmada','cancelada','completada','afectada_por_configuracion','cancelada_por_complejo','reprogramada') NOT NULL DEFAULT 'pendiente'");
            }
            DB::statement("ALTER TABLE reservas ADD activa TINYINT GENERATED ALWAYS AS (CASE WHEN estado IN ('cancelada','cancelada_por_complejo','reprogramada') THEN NULL ELSE 1 END) STORED");
            Schema::table('reservas', function (Blueprint $table) {
                $table->unique(['cancha_id', 'fecha', 'horario_id', 'activa'], 'reservas_sin_duplicados');
            });
        } else {
            Schema::table('reservas', function (Blueprint $table) {
                $table->dropColumn('activa');
            });
            Schema::table('reservas', function (Blueprint $table) {
                $column = $table->tinyInteger('activa')->nullable();
                $expression = "CASE WHEN estado IN ('cancelada','cancelada_por_complejo','reprogramada') THEN NULL ELSE 1 END";
                DB::getDriverName() === 'pgsql'
                    ? $column->storedAs($expression)
                    : $column->virtualAs($expression);
            });
            Schema::table('reservas', function (Blueprint $table) {
                $table->unique(['cancha_id', 'fecha', 'horario_id', 'activa'], 'reservas_sin_duplicados');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE reservas SET estado = 'cancelada' WHERE estado IN ('afectada_por_configuracion','cancelada_por_complejo','reprogramada')");
            Schema::table('reservas', fn (Blueprint $table) => $table->dropUnique('reservas_sin_duplicados'));
            DB::statement('ALTER TABLE reservas DROP COLUMN activa');
            DB::statement("ALTER TABLE reservas MODIFY estado ENUM('pendiente','confirmada','cancelada','completada') NOT NULL DEFAULT 'pendiente'");
            DB::statement("ALTER TABLE reservas ADD activa TINYINT GENERATED ALWAYS AS (CASE WHEN estado = 'cancelada' THEN NULL ELSE 1 END) STORED");
            Schema::table('reservas', fn (Blueprint $table) => $table->unique(['cancha_id', 'fecha', 'horario_id', 'activa'], 'reservas_sin_duplicados'));
        } else {
            Schema::table('reservas', fn (Blueprint $table) => $table->dropUnique('reservas_sin_duplicados'));
            Schema::table('reservas', fn (Blueprint $table) => $table->dropColumn('activa'));
            Schema::table('reservas', function (Blueprint $table) {
                $column = $table->tinyInteger('activa')->nullable();
                $expression = "CASE WHEN estado = 'cancelada' THEN NULL ELSE 1 END";
                DB::getDriverName() === 'pgsql'
                    ? $column->storedAs($expression)
                    : $column->virtualAs($expression);
            });
            Schema::table('reservas', fn (Blueprint $table) => $table->unique(['cancha_id', 'fecha', 'horario_id', 'activa'], 'reservas_sin_duplicados'));
        }
    }
};
