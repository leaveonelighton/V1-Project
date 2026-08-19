<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    if (lol_wants_json()) {
        lol_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    lol_render_shell('Private Conversation', '<h1>Private Conversation</h1><p>Please return to <a href="/communicate/check-response.html">Check My Response</a>.</p>');
}

try {
    $pdo = lol_db();
    lol_enforce_rate_limit($pdo, 'follow_up', 12, 3600);

    $code = lol_text($_POST['private_code'] ?? '', 120);
    $message = lol_text($_POST['message'] ?? '', 4000);
    if (strlen(lol_normalize_code($code)) < 20) {
        throw new RuntimeException('Please enter the complete private response code.');
    }
    if (strlen($message) < 2) {
        throw new RuntimeException('Please enter a message.');
    }

    $find = $pdo->prepare('SELECT id, status, response_method, public_reference FROM conversations WHERE secret_hash = ? LIMIT 1');
    $find->execute([lol_code_hash($code)]);
    $conversation = $find->fetch();
    if (!$conversation) {
        throw new RuntimeException('No conversation was found for that private code.');
    }
    if ($conversation['status'] !== 'open') {
        throw new RuntimeException('This conversation is closed. Start a new message if you need to contact us again.');
    }
    if ($conversation['response_method'] === 'none') {
        throw new RuntimeException('This conversation was submitted with no reply requested.');
    }

    $pdo->beginTransaction();
    $insert = $pdo->prepare('INSERT INTO messages (conversation_id, sender, message_body) VALUES (?, ?, ?)');
    $insert->execute([(int)$conversation['id'], 'visitor', $message]);
    $update = $pdo->prepare('UPDATE conversations SET last_activity_at = UTC_TIMESTAMP() WHERE id = ?');
    $update->execute([(int)$conversation['id']]);
    lol_audit($pdo, (int)$conversation['id'], 'visitor_follow_up_posted');
    $pdo->commit();

    if (lol_wants_json()) {
        lol_json([
            'ok' => true,
            'reference' => $conversation['public_reference'],
            'message' => 'Your follow-up was added to the private conversation.',
        ], 201);
    }

    lol_render_shell(
        'Follow-up Received',
        '<p class="section-label">Private conversation</p><h1>Follow-up received.</h1>'
        . '<p>Reference: <strong>' . lol_html_escape((string)$conversation['public_reference']) . '</strong></p>'
        . '<p>Your message was added to the conversation.</p>'
        . '<form action="/api/messages/check.php" method="post"><input type="hidden" name="private_code" value="' . lol_html_escape($code) . '"><button class="button" type="submit">Return to Conversation</button></form>'
    );
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $message = $e instanceof RuntimeException ? $e->getMessage() : 'We could not add that message right now.';
    if (lol_wants_json()) {
        lol_json(['ok' => false, 'error' => $message], 400);
    }
    lol_render_shell('Follow-up Not Sent', '<h1>Follow-up not sent.</h1><p>' . lol_html_escape($message) . '</p><p><a href="/communicate/check-response.html">Return to Check My Response</a></p>');
}
