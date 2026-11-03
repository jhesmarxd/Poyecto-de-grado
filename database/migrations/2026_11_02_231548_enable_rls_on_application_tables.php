<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // El esquema de la aplicación no se expone al Data API de Supabase.
        // RLS añade protección si se expone en el futuro.
        $schema = (string) DB::getConfig('search_path');
        if (! preg_match('/^[a-z_][a-z0-9_]*$/', $schema)) {
            throw new InvalidArgumentException('DB_SCHEMA no es un nombre de esquema válido.');
        }

        $tables = [
            'ajustes', 'balones', 'cache', 'cache_locks',
            'cambios_configuracion_reserva', 'canchas', 'chatbot_ejecuciones',
            'cierres_cancha', 'configuraciones_reserva', 'conversaciones',
            'excepciones_reserva', 'failed_jobs', 'google_calendar_connections',
            'google_calendar_events', 'horarios', 'horarios_semanales',
            'job_batches', 'jobs', 'mensajes_chatbot', 'migrations',
            'notifications', 'password_reset_tokens', 'reservas',
            'reservas_afectadas_configuracion', 'sessions', 'social_accounts',
            'users',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement('ALTER TABLE "'.$schema.'"."'.$table.'" ENABLE ROW LEVEL SECURITY');
            }
        }
    }

    public function down(): void
    {
        // Desactivar RLS durante un rollback expondría datos al Data API.
    }
};
