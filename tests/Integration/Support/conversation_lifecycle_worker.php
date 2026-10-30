<?php

use App\Models\User;
use App\Services\Chatbot\ConversationLifecycleService;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$barrierDirectory = (string) getenv('LIFECYCLE_BARRIER_DIRECTORY');
$token = (string) getenv('LIFECYCLE_WORKER_TOKEN');
$operation = (string) getenv('LIFECYCLE_OPERATION');
$userId = (int) getenv('LIFECYCLE_USER_ID');
$conversationId = filter_var(
    getenv('LIFECYCLE_CONVERSATION_ID'),
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]],
);

file_put_contents(
    $barrierDirectory.DIRECTORY_SEPARATOR."ready-{$token}",
    'ready',
);

$deadline = microtime(true) + 10;
while (! is_file($barrierDirectory.DIRECTORY_SEPARATOR.'go')) {
    if (microtime(true) >= $deadline) {
        throw new RuntimeException('Tiempo agotado esperando la barrera.');
    }

    usleep(10_000);
}

$usuario = User::query()->findOrFail($userId);
$lifecycle = $app->make(ConversationLifecycleService::class);
$conversacion = $operation === 'nueva'
    ? $lifecycle->iniciarNueva($usuario, $conversationId)
    : $lifecycle->obtenerOCrearActiva($usuario, $conversationId);

fwrite(STDOUT, 'LIFECYCLE_RESULT:'.json_encode([
    'conversation_id' => $conversacion->id,
    'status' => $conversacion->status,
], JSON_THROW_ON_ERROR));
