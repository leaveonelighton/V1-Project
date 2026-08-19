<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require dirname(__DIR__) . '/api/messages/_bootstrap.php';

$reference = trim((string)($argv[1] ?? ''));
$confirm = (string)($argv[2] ?? '');

if ($reference === '') {
    fwrite(STDERR, "Usage: php maintenance/private-message-cleanup.php PUBLIC_REFERENCE --confirm\n");
    exit(2);
}

try {
    $pdo = lol_db();
    $find = $pdo->prepare('SELECT id, public_reference, status, created_at FROM conversations WHERE public_reference = ? LIMIT 1');
    $find->execute([$reference]);
    $conversation = $find->fetch();

    if (!$conversation) {
        fwrite(STDERR, "Conversation not found.\n");
        exit(3);
    }

    echo 'Found: ' . $conversation['public_reference'] . ' | ' . $conversation['status'] . ' | ' . $conversation['created_at'] . "\n";

    if ($confirm !== '--confirm') {
        echo "Dry run only. Add --confirm to delete this conversation and its messages.\n";
        exit(0);
    }

    $pdo->beginTransaction();
    $audit = $pdo->prepare('DELETE FROM audit_events WHERE conversation_id = ?');
    $audit->execute([(int)$conversation['id']]);
    $delete = $pdo->prepare('DELETE FROM conversations WHERE id = ?');
    $delete->execute([(int)$conversation['id']]);
    $pdo->commit();

    echo "DELETED\n";
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "CLEANUP FAILED\n");
    exit(1);
}
