<?php
declare(strict_types=1);
require __DIR__ . '/_admin.php';

$pdo = lol_db();
$id = (int)($_GET['id'] ?? 0);
if ($id < 1) {
    http_response_code(400);
    lol_admin_header('Conversation');
    echo '<h1>Conversation not found.</h1><p><a href="index.php">Return to inbox</a></p>';
    lol_admin_footer();
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM conversations WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$conversation = $stmt->fetch();
if (!$conversation) {
    http_response_code(404);
    lol_admin_header('Conversation');
    echo '<h1>Conversation not found.</h1><p><a href="index.php">Return to inbox</a></p>';
    lol_admin_footer();
    exit;
}

$messagesStmt = $pdo->prepare('SELECT sender, message_body, created_at FROM messages WHERE conversation_id = ? ORDER BY created_at ASC, id ASC');
$messagesStmt->execute([$id]);
$messages = $messagesStmt->fetchAll();
$contact = lol_decrypt_contact($conversation['contact_ciphertext'] ?? null);
$csrf = lol_csrf_token();

lol_admin_header('Conversation ' . (string)$conversation['public_reference']);
echo '<p><a href="index.php">← Back to inbox</a></p>'
    . '<h1>' . lol_html_escape((string)$conversation['public_reference']) . '</h1>'
    . '<div class="conversation-summary">'
    . '<p><strong>Status:</strong> ' . lol_html_escape((string)$conversation['status']) . '</p>'
    . '<p><strong>Topic:</strong> ' . lol_html_escape((string)$conversation['topic']) . '</p>'
    . '<p><strong>Name/nickname:</strong> ' . lol_html_escape((string)($conversation['nickname'] ?: 'Not provided')) . '</p>'
    . '<p><strong>Response method:</strong> ' . lol_html_escape((string)$conversation['response_method']) . '</p>'
    . '<p><strong>Contact:</strong> ' . lol_html_escape($contact ?: 'Not provided') . '</p>'
    . '<p><strong>Created:</strong> ' . lol_html_escape((string)$conversation['created_at']) . '</p>'
    . '</div><div class="message-thread">';

foreach ($messages as $message) {
    $sender = $message['sender'] === 'admin' ? 'Leave One Light On' : 'Visitor';
    echo '<article class="message-card ' . ($message['sender'] === 'admin' ? 'message-admin' : 'message-visitor') . '">'
        . '<p class="message-meta"><strong>' . lol_html_escape($sender) . '</strong> · ' . lol_html_escape((string)$message['created_at']) . '</p>'
        . '<p>' . nl2br(lol_html_escape((string)$message['message_body'])) . '</p></article>';
}
echo '</div>';

if ($conversation['status'] === 'open' && $conversation['response_method'] !== 'none') {
    echo '<section class="admin-reply-box"><h2>Reply</h2>'
        . '<form action="reply.php" method="post">'
        . '<input type="hidden" name="conversation_id" value="' . $id . '">'
        . '<input type="hidden" name="csrf_token" value="' . lol_html_escape($csrf) . '">'
        . '<label for="reply-message">Message</label>'
        . '<textarea id="reply-message" name="message" rows="8" maxlength="4000" required></textarea>'
        . '<button class="button" type="submit">Post Private Reply</button>'
        . '</form></section>';
}

if ($conversation['status'] === 'open') {
    echo '<form class="admin-close-form" action="close.php" method="post">'
        . '<input type="hidden" name="conversation_id" value="' . $id . '">'
        . '<input type="hidden" name="csrf_token" value="' . lol_html_escape($csrf) . '">'
        . '<button class="button button-outline" type="submit">Close Conversation</button>'
        . '</form>';
}

lol_admin_footer();
