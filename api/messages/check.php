<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    if (lol_wants_json()) {
        lol_json(['ok' => false, 'error' => 'POST required.'], 405);
    }
    lol_render_shell('Check My Response', '<h1>Check My Response</h1><p>Please use the <a href="/communicate/check-response.html">response lookup form</a>.</p>');
}

try {
    $pdo = lol_db();
    lol_enforce_rate_limit($pdo, 'check', 30, 3600);

    $code = lol_text($_POST['private_code'] ?? '', 120);
    if (strlen(lol_normalize_code($code)) < 20) {
        throw new RuntimeException('Please enter the complete private response code.');
    }

    $find = $pdo->prepare(
        'SELECT id, public_reference, topic, status, created_at, last_activity_at, closed_at
         FROM conversations WHERE secret_hash = ? LIMIT 1'
    );
    $find->execute([lol_code_hash($code)]);
    $conversation = $find->fetch();
    if (!$conversation) {
        throw new RuntimeException('No conversation was found for that private code. Check the code and try again.');
    }

    $messagesStmt = $pdo->prepare(
        'SELECT sender, message_body, created_at
         FROM messages WHERE conversation_id = ? ORDER BY created_at ASC, id ASC'
    );
    $messagesStmt->execute([(int)$conversation['id']]);
    $messages = $messagesStmt->fetchAll();

    lol_audit($pdo, (int)$conversation['id'], 'visitor_checked_response');

    if (lol_wants_json()) {
        lol_json([
            'ok' => true,
            'conversation' => [
                'reference' => $conversation['public_reference'],
                'topic' => $conversation['topic'],
                'status' => $conversation['status'],
                'created_at' => $conversation['created_at'],
                'last_activity_at' => $conversation['last_activity_at'],
                'messages' => array_map(static fn(array $message): array => [
                    'sender' => $message['sender'],
                    'message' => $message['message_body'],
                    'created_at' => $message['created_at'],
                ], $messages),
            ],
        ]);
    }

    $threadHtml = '';
    foreach ($messages as $message) {
        $sender = $message['sender'] === 'admin' ? 'Leave One Light On' : 'You';
        $threadHtml .= '<article class="message-card ' . ($message['sender'] === 'admin' ? 'message-admin' : 'message-visitor') . '">'
            . '<p class="message-meta"><strong>' . lol_html_escape($sender) . '</strong> · '
            . lol_html_escape((string)$message['created_at']) . '</p>'
            . '<p>' . nl2br(lol_html_escape((string)$message['message_body'])) . '</p></article>';
    }

    lol_render_shell(
        'Private Conversation',
        '<p class="section-label">Private conversation</p><h1>' . lol_html_escape((string)$conversation['public_reference']) . '</h1>'
        . '<p>Status: <strong>' . lol_html_escape(ucfirst((string)$conversation['status'])) . '</strong></p>'
        . '<div class="message-thread">' . $threadHtml . '</div>'
        . '<p><a class="button" href="/communicate/check-response.html">Check Again Later</a></p>'
    );
} catch (Throwable $e) {
    $message = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'We could not check that conversation right now. Please try again later.';

    if (lol_wants_json()) {
        lol_json(['ok' => false, 'error' => $message], 400);
    }

    lol_render_shell(
        'Response Not Found',
        '<h1>We could not open that conversation.</h1><p>' . lol_html_escape($message) . '</p>'
        . '<p><a class="button" href="/communicate/check-response.html">Try Again</a></p>'
    );
}
