<?php

declare(strict_types=1);

use App\Exceptions\Chat\PtChatWorkflowException;
use App\Exceptions\Pt\PtWorkflowException;
use App\Models\NguoiDung;
use App\Models\TinNhan;
use App\Services\Pt\Chat\PtChatConversationService;
use App\Services\Pt\Chat\PtChatMessageService;
use App\Services\Pt\PtAssignmentService;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[
    $script,
    $database,
    $mode,
    $actorId,
    $assignmentId,
    $conversationId,
    $clientMessageId,
    $content,
    $barrier,
    $output,
] = $argv;
if (preg_match('/^smart_fitness_chat_test_/i', $database) !== 1 || strtolower($database) === 'smart_fitness') {
    fwrite(STDERR, "Unsafe PT Chat concurrency database.\n");
    exit(2);
}

putenv('APP_ENV=testing');
putenv('DB_CONNECTION=mysql');
putenv('DB_DATABASE='.$database);
putenv('BROADCAST_CONNECTION=null');
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'mysql';
$_ENV['DB_DATABASE'] = $database;
$_ENV['BROADCAST_CONNECTION'] = 'null';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$deadline = microtime(true) + 20;
while (! is_file($barrier)) {
    if (microtime(true) >= $deadline) {
        fwrite(STDERR, "PT Chat start barrier timeout.\n");
        exit(3);
    }
    usleep(10_000);
}

try {
    $actor = NguoiDung::query()->findOrFail((int) $actorId);
    if ($mode === 'conversation') {
        $result = $app->make(PtChatConversationService::class)->hienTai($actor);
        $line = ['status' => 'success', 'conversation_id' => $result['id']];
    } elseif ($mode === 'send') {
        $result = $app->make(PtChatMessageService::class)->gui($actor, (int) $conversationId, [
            'client_message_id' => $clientMessageId,
            'content' => $content,
        ]);
        $message = TinNhan::query()->findOrFail($result['message']['id']);
        $line = [
            'status' => 'success',
            'message_id' => $result['message']['id'],
            'replayed' => $result['replayed'],
            'usage_id' => $message->su_dung_quyen_loi_id,
        ];
    } elseif ($mode === 'end') {
        $result = $app->make(PtAssignmentService::class)->ketThuc(
            $actor,
            (int) $assignmentId,
            'Concurrency test end',
        );
        $line = ['status' => 'success', 'assignment_id' => $result['id'], 'ended_at' => $result['end_at']];
    } else {
        fwrite(STDERR, "Unknown PT Chat action.\n");
        exit(4);
    }
} catch (PtChatWorkflowException|PtWorkflowException $exception) {
    $line = [
        'status' => 'error',
        'code' => $exception->safeCode,
        'http_status' => $exception->responseStatus,
    ];
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception).': '.$exception->getMessage()."\n");
    exit(1);
}

file_put_contents($output, json_encode($line, JSON_UNESCAPED_UNICODE).PHP_EOL, FILE_APPEND | LOCK_EX);
