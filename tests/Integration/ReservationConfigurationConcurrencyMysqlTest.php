<?php

namespace Tests\Integration;

use App\Models\CambioConfiguracionReserva;
use App\Models\Cancha;
use App\Models\ExcepcionReserva;
use App\Models\Horario;
use App\Models\Reserva;
use App\Models\User;
use App\Services\ConfiguracionReservaService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ReservationConfigurationConcurrencyMysqlTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'mysql'
            || ! filter_var(env('RUN_MYSQL_CONCURRENCY_TESTS'), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Requiere MySQL aislado y RUN_MYSQL_CONCURRENCY_TESTS=true.');
        }
    }

    public function test_confirmar_reserva_y_aplicar_cierre_no_dejan_una_reserva_activa_en_conflicto(): void
    {
        $fecha = Carbon::now('America/La_Paz')->addDays(2);
        while ($fecha->dayOfWeekIso === 7) {
            $fecha->addDay();
        }

        $cancha = Cancha::factory()->create(['nombre' => 'Cancha concurrencia']);
        $horario = Horario::factory()->create([
            'cancha_id' => $cancha->id,
            'dia_semana' => $fecha->dayOfWeekIso,
            'hora_inicio' => '18:00:00',
            'hora_fin' => '19:00:00',
            'disponible' => true,
        ]);
        $cliente = User::factory()->create(['rol' => User::ROL_CLIENTE]);
        $administrador = User::factory()->create(['rol' => User::ROL_ADMIN]);
        $cambio = app(ConfiguracionReservaService::class)->prepararExcepcion([
            'cancha_id' => $cancha->id,
            'fecha' => $fecha->toDateString(),
            'tipo' => ExcepcionReserva::CIERRE_COMPLETO,
            'motivo' => 'Cierre concurrente',
        ], $administrador);

        $resultados = $this->ejecutarEnCompetencia($cambio, $cliente, $cancha, $horario, $fecha->toDateString());

        $this->assertSame(CambioConfiguracionReserva::ESTADO_APLICADO, $cambio->fresh()->estado);
        $this->assertSame(ExcepcionReserva::ESTADO_APLICADA, $cambio->excepciones()->firstOrFail()->estado);
        $this->assertFalse(
            Reserva::query()
                ->where('cancha_id', $cancha->id)
                ->whereDate('fecha', $fecha)
                ->whereIn('estado', [Reserva::ESTADO_PENDIENTE, Reserva::ESTADO_CONFIRMADA])
                ->exists()
        );

        $reserva = Reserva::query()->where('user_id', $cliente->id)->first();
        if ($reserva) {
            $this->assertSame(Reserva::ESTADO_AFECTADA, $reserva->estado);
            $this->assertDatabaseHas('reservas_afectadas_configuracion', [
                'cambio_configuracion_id' => $cambio->id,
                'reserva_id' => $reserva->id,
            ]);
        } else {
            $reservaResultado = collect($resultados)->firstWhere('operation', 'reservar');
            $this->assertSame('rejected', $reservaResultado['status']);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function ejecutarEnCompetencia(
        CambioConfiguracionReserva $cambio,
        User $cliente,
        Cancha $cancha,
        Horario $horario,
        string $fecha,
    ): array {
        $directory = storage_path('framework/testing/reservation-config-concurrency-'.Str::uuid());
        File::ensureDirectoryExists($directory);
        $procesos = [];

        try {
            foreach (['reservar', 'aplicar'] as $operacion) {
                $process = new Process(
                    [PHP_BINARY, base_path('tests/Integration/Support/reservation_configuration_worker.php')],
                    base_path(),
                    $this->workerEnvironment($directory, $operacion, $cambio, $cliente, $cancha, $horario, $fecha),
                );
                $process->setTimeout(30);
                $process->start();
                $procesos[$operacion] = $process;
            }

            $this->esperarBarrera($directory, $procesos);
            File::put($directory.DIRECTORY_SEPARATOR.'go', 'go');

            $resultados = [];
            foreach ($procesos as $operacion => $process) {
                $this->assertSame(0, $process->wait(), "El worker {$operacion} falló: ".$process->getErrorOutput());
                preg_match('/RESERVATION_CONFIG_RESULT:(\{.*\})/', $process->getOutput(), $matches);
                $this->assertArrayHasKey(1, $matches, "El worker {$operacion} no devolvió un resultado.");
                $resultados[] = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
            }

            return $resultados;
        } finally {
            foreach ($procesos as $process) {
                if ($process->isRunning()) {
                    $process->stop(1);
                }
            }
            File::deleteDirectory($directory);
        }
    }

    /** @param array<string, Process> $procesos */
    private function esperarBarrera(string $directory, array $procesos): void
    {
        $deadline = microtime(true) + 10;
        while (microtime(true) < $deadline) {
            if (collect(array_keys($procesos))->every(
                fn (string $token): bool => File::exists($directory.DIRECTORY_SEPARATOR."ready-{$token}")
            )) {
                return;
            }
            usleep(10_000);
        }

        $this->fail('Los workers no alcanzaron la barrera de concurrencia.');
    }

    /** @return array<string, string> */
    private function workerEnvironment(
        string $directory,
        string $operation,
        CambioConfiguracionReserva $cambio,
        User $cliente,
        Cancha $cancha,
        Horario $horario,
        string $fecha,
    ): array {
        $mysql = config('database.connections.mysql');

        return [
            'APP_ENV' => 'testing',
            'APP_KEY' => (string) config('app.key'),
            'CACHE_STORE' => 'array',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => (string) $mysql['host'],
            'DB_PORT' => (string) $mysql['port'],
            'DB_DATABASE' => (string) $mysql['database'],
            'DB_USERNAME' => (string) $mysql['username'],
            'DB_PASSWORD' => (string) $mysql['password'],
            'QUEUE_CONNECTION' => 'sync',
            'GOOGLE_CALENDAR_ENABLED' => 'false',
            'RESERVATION_CONFIG_BARRIER_DIRECTORY' => $directory,
            'RESERVATION_CONFIG_WORKER_TOKEN' => $operation,
            'RESERVATION_CONFIG_OPERATION' => $operation,
            'RESERVATION_CONFIG_CHANGE_ID' => (string) $cambio->id,
            'RESERVATION_CONFIG_USER_ID' => (string) $cliente->id,
            'RESERVATION_CONFIG_CANCHA_ID' => (string) $cancha->id,
            'RESERVATION_CONFIG_HORARIO_ID' => (string) $horario->id,
            'RESERVATION_CONFIG_FECHA' => $fecha,
            'RESERVATION_CONFIG_HORA_FIN' => '19:00',
        ];
    }
}
