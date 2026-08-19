<?php
declare(strict_types=1);
require __DIR__ . '/_admin.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: index.php', true, 303);
    exit;
}

try {
    lol_verify_csrf();
    $pdo = lol_db();
    $id = (int)($_POST['conversation_id'] ?? 0);
    if ($id < 1) {
        throw new RuntimeException('Conversation not found.');
    }

    $update = $pdo->prepare(
        "UPDATE conversations
         SET status = 'closed', closed_at = UTC_TIMESTAMP(), last_activity_at = UTC_TIMESTAMP()
         WHERE id = ? AND status = 'open'"
    );
    $update->execute([$id]);
    if ($update->rowCount() < 1) {
        throw new RuntimeException('This conversation is already closed or could not be found.');
    }
    lol_audit($pdo, $id, 'conversation_closed');

    header('Location: conversation.php?id=' . $id, true, 303);
    exit;
} catch (Throwable $e) {
    http_response_code(400);
    lol_admin_header('Conversation Not Closed');
    echo '<h1>Conversation not closed.</h1><p>' . lol_html_escape($e->getMessage()) . '</p><p><a href="index.php">Return to inbox</a></p>';
    lol_admin_footer();
}
