<?php

namespace App\Console\Commands;

use App\Models\Reserva;
use App\Services\ReservaNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class EnviarRecordatoriosReservas extends Command
{
    protected $signature = 'reservas:enviar-recordatorios';

    protected $description = 'Genera recordatorios internos pendientes para reservas confirmadas';

    public function handle(ReservaNotificationService $notificaciones): int
    {
        $zonaHoraria = (string) config('app.timezone', 'UTC');
        $ahora = Carbon::now($zonaHoraria);
        $enviadas = 0;

        Reserva::query()
            ->with(['usuario', 'cancha', 'horario'])
            ->where('estado', Reserva::ESTADO_CONFIRMADA)
            ->whereBetween('fecha', [
                $ahora->copy()->subDay()->toDateString(),
                $ahora->copy()->addDay()->toDateString(),
            ])
            ->get()
            ->each(function (Reserva $reserva) use ($ahora, $notificaciones, &$enviadas): void {
                $tipo = $this->tipoPendiente($reserva, $ahora);

                if ($tipo && $notificaciones->enviar($reserva, $tipo)) {
                    $enviadas++;
                }
            });

        $this->info("Notificaciones generadas: {$enviadas}");

        return self::SUCCESS;
    }

    private function tipoPendiente(Reserva $reserva, Carbon $ahora): ?string
    {
        $inicio = $reserva->iniciaEn();
        $fin = $reserva->finalizaEn();

        if ($ahora->greaterThanOrEqualTo($fin)) {
            return ReservaNotificationService::TIPO_FINALIZADA;
        }

        if ($ahora->greaterThanOrEqualTo($inicio)) {
            return ReservaNotificationService::TIPO_INICIADA;
        }

        if ($ahora->greaterThanOrEqualTo($inicio->copy()->subMinutes(30))) {
            return ReservaNotificationService::TIPO_RECORDATORIO_30M;
        }

        if ($ahora->greaterThanOrEqualTo($inicio->copy()->subHours(2))) {
            return ReservaNotificationService::TIPO_RECORDATORIO_2H;
        }

        return null;
    }
}
