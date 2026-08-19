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
    $message = lol_text($_POST['message'] ?? '', 4000);
    if ($id < 1 || strlen($message) < 1) {
        throw new RuntimeException('A conversation and reply are required.');
    }

    $check = $pdo->prepare('SELECT status, response_method FROM conversations WHERE id = ? LIMIT 1');
    $check->execute([$id]);
    $conversation = $check->fetch();
    if (!$conversation || $conversation['status'] !== 'open') {
        throw new RuntimeException('This conversation is not open.');
    }
    if ($conversation['response_method'] === 'none') {
        throw new RuntimeException('The visitor requested no reply.');
    }

    $pdo->beginTransaction();
    $insert = $pdo->prepare('INSERT INTO messages (conversation_id, sender, message_body) VALUES (?, ?, ?)');
    $insert->execute([$id, 'admin', $message]);
    $update = $pdo->prepare('UPDATE conversations SET last_activity_at = UTC_TIMESTAMP() WHERE id = ?');
    $update->execute([$id]);
    lol_audit($pdo, $id, 'admin_reply_posted');
    $pdo->commit();

    header('Location: conversation.php?id=' . $id, true, 303);
    exit;
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    lol_admin_header('Reply Not Posted');
    echo '<h1>Reply not posted.</h1><p>' . lol_html_escape($e->getMessage()) . '</p><p><a href="index.php">Return to inbox</a></p>';
    lol_admin_footer();
}
