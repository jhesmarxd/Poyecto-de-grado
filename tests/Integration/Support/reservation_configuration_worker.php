<?php

use App\Models\CambioConfiguracionReserva;
use App\Models\User;
use App\Services\ConfiguracionReservaService;
use App\Services\ReservaService;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$directory = (string) getenv('RESERVATION_CONFIG_BARRIER_DIRECTORY');
$token = (string) getenv('RESERVATION_CONFIG_WORKER_TOKEN');
$operation = (string) getenv('RESERVATION_CONFIG_OPERATION');

file_put_contents($directory.DIRECTORY_SEPARATOR."ready-{$token}", 'ready');

$deadline = microtime(true) + 10;
while (! is_file($directory.DIRECTORY_SEPARATOR.'go')) {
    if (microtime(true) >= $deadline) {
        throw new RuntimeException('Tiempo agotado esperando la barrera.');
    }

    usleep(10_000);
}

try {
    if ($operation === 'reservar') {
        $usuario = User::query()->findOrFail((int) getenv('RESERVATION_CONFIG_USER_ID'));
        $reserva = $app->make(ReservaService::class)->crearReserva($usuario, [
            'cancha_id' => (int) getenv('RESERVATION_CONFIG_CANCHA_ID'),
            'horario_id' => (int) getenv('RESERVATION_CONFIG_HORARIO_ID'),
            'fecha' => (string) getenv('RESERVATION_CONFIG_FECHA'),
            'hora_fin' => (string) getenv('RESERVATION_CONFIG_HORA_FIN'),
        ]);
        $resultado = ['operation' => $operation, 'status' => 'created', 'reservation_id' => $reserva->id];
    } else {
        $cambio = CambioConfiguracionReserva::query()->findOrFail(
            (int) getenv('RESERVATION_CONFIG_CHANGE_ID')
        );
        $app->make(ConfiguracionReservaService::class)->aplicar($cambio);
        $resultado = ['operation' => $operation, 'status' => 'applied'];
    }
} catch (Throwable $exception) {
    $resultado = [
        'operation' => $operation,
        'status' => 'rejected',
        'code' => $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : $exception->getCode(),
        'exception' => $exception::class,
    ];
}

fwrite(STDOUT, 'RESERVATION_CONFIG_RESULT:'.json_encode($resultado, JSON_THROW_ON_ERROR));
