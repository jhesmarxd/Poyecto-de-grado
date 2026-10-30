<?php

namespace Tests\Integration;

use App\Models\Conversacion;
use App\Models\User;
use App\Services\Chatbot\ConversationLifecycleService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConversationLifecycleConcurrencyMysqlTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'mysql'
            || ! filter_var(env('RUN_MYSQL_CONCURRENCY_TESTS'), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped(
                'Requiere MySQL aislado y RUN_MYSQL_CONCURRENCY_TESTS=true.'
            );
        }

        config()->set('chatbot.provider', null);
        config()->set('chatbot.api_key', null);
    }

    public function test_dos_operaciones_simultaneas_obtienen_la_misma_primera_conversacion(): void
    {
        $usuario = User::factory()->create();

        $resultados = $this->ejecutarEnCompetencia('obtener', $usuario->id);

        $this->assertSame($resultados[0]['conversation_id'], $resultados[1]['conversation_id']);
        $this->assertSame(1, $this->conversacionesActivas($usuario->id));
    }

    public function test_dos_nuevas_conversaciones_simultaneas_dejan_una_sola_activa(): void
    {
        $usuario = User::factory()->create();
        $anterior = app(ConversationLifecycleService::class)
            ->obtenerOCrearActiva($usuario);

        $resultados = $this->ejecutarEnCompetencia(
            'nueva',
            $usuario->id,
            $anterior->id,
        );
        $idsDevueltos = array_column($resultados, 'conversation_id');
        $activa = Conversacion::query()
            ->where('user_id', $usuario->id)
            ->where('status', Conversacion::STATUS_ACTIVE)
            ->sole();

        $this->assertContains($activa->id, $idsDevueltos);
        $this->assertSame(1, $this->conversacionesActivas($usuario->id));
        $this->assertSame(Conversacion::STATUS_CLOSED, $anterior->fresh()->status);
        $this->assertCount(2, array_unique($idsDevueltos));
    }

    /**
     * @return array<int, array{conversation_id: int, status: string}>
     */
    private function ejecutarEnCompetencia(
        string $operacion,
        int $userId,
        ?int $conversationId = null,
    ): array {
        $barrierDirectory = storage_path(
            'framework/testing/lifecycle-concurrency-'.Str::uuid()
        );
        File::ensureDirectoryExists($barrierDirectory);

        $procesos = [];

        try {
            foreach (['one', 'two'] as $token) {
                $process = new Process(
                    [PHP_BINARY, base_path('tests/Integration/Support/conversation_lifecycle_worker.php')],
                    base_path(),
                    $this->workerEnvironment(
                        $barrierDirectory,
                        $token,
                        $operacion,
                        $userId,
                        $conversationId,
                    ),
                );
                $process->setTimeout(30);
                $process->start();
                $procesos[$token] = $process;
            }

            $this->esperarBarrera($barrierDirectory, array_keys($procesos), $procesos);
            File::put($barrierDirectory.DIRECTORY_SEPARATOR.'go', 'go');

            $resultados = [];
            foreach ($procesos as $token => $process) {
                $exitCode = $process->wait();
                $this->assertSame(
                    0,
                    $exitCode,
                    "El worker {$token} falló: ".$process->getErrorOutput(),
                );
                preg_match(
                    '/LIFECYCLE_RESULT:(\{.*\})/',
                    $process->getOutput(),
                    $matches,
                );
                $this->assertArrayHasKey(
                    1,
                    $matches,
                    "El worker {$token} no devolvió un resultado reconocible.",
                );
                $resultados[] = json_decode(
                    $matches[1],
                    true,
                    flags: JSON_THROW_ON_ERROR,
                );
            }

            return $resultados;
        } finally {
            foreach ($procesos as $process) {
                if ($process->isRunning()) {
                    $process->stop(1);
                }
            }

            File::deleteDirectory($barrierDirectory);
        }
    }

    /**
     * @param  array<int, string>  $tokens
     * @param  array<string, Process>  $procesos
     */
    private function esperarBarrera(
        string $directory,
        array $tokens,
        array $procesos,
    ): void {
        $deadline = microtime(true) + 10;

        while (microtime(true) < $deadline) {
            $ready = collect($tokens)->every(fn (string $token): bool => File::exists(
                $directory.DIRECTORY_SEPARATOR."ready-{$token}"
            ));
            if ($ready) {
                return;
            }

            foreach ($procesos as $token => $process) {
                if (! $process->isRunning() && ! File::exists(
                    $directory.DIRECTORY_SEPARATOR."ready-{$token}"
                )) {
                    $this->fail("El worker {$token} terminó antes de alcanzar la barrera: "
                        .$process->getErrorOutput());
                }
            }

            usleep(10_000);
        }

        $this->fail('Los dos workers no alcanzaron la barrera de concurrencia.');
    }

    /** @return array<string, string> */
    private function workerEnvironment(
        string $barrierDirectory,
        string $token,
        string $operation,
        int $userId,
        ?int $conversationId,
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
            'CHATBOT_PROVIDER' => '',
            'CHATBOT_API_KEY' => '',
            'LIFECYCLE_BARRIER_DIRECTORY' => $barrierDirectory,
            'LIFECYCLE_WORKER_TOKEN' => $token,
            'LIFECYCLE_OPERATION' => $operation,
            'LIFECYCLE_USER_ID' => (string) $userId,
            'LIFECYCLE_CONVERSATION_ID' => (string) ($conversationId ?? ''),
        ];
    }

    private function conversacionesActivas(int $userId): int
    {
        return Conversacion::query()
            ->where('user_id', $userId)
            ->where('status', Conversacion::STATUS_ACTIVE)
            ->count();
    }
}
