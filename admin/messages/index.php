<?php
declare(strict_types=1);
require __DIR__ . '/_admin.php';

$pdo = lol_db();
$status = (string)($_GET['status'] ?? 'open');
if (!in_array($status, ['open', 'closed', 'all'], true)) {
    $status = 'open';
}

$sql = 'SELECT id, public_reference, topic, nickname, response_method, status, created_at, last_activity_at
        FROM conversations';
$params = [];
if ($status !== 'all') {
    $sql .= ' WHERE status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY last_activity_at DESC LIMIT 100';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$conversations = $stmt->fetchAll();

lol_admin_header('Private Messages');
echo '<div class="admin-heading"><div><h1>Private Messages</h1><p>Review, answer, and close website conversations.</p></div>'
    . '<nav aria-label="Message filters"><a href="?status=open">Open</a> · <a href="?status=closed">Closed</a> · <a href="?status=all">All</a></nav></div>';

if (!$conversations) {
    echo '<p>No conversations match this filter.</p>';
} else {
    echo '<div class="admin-message-list">';
    foreach ($conversations as $conversation) {
        $nickname = trim((string)($conversation['nickname'] ?? ''));
        $label = $nickname !== '' ? $nickname : 'Anonymous visitor';
        echo '<article class="admin-message-card">'
            . '<p class="card-label">' . lol_html_escape((string)$conversation['status']) . '</p>'
            . '<h2><a href="conversation.php?id=' . (int)$conversation['id'] . '">' . lol_html_escape((string)$conversation['public_reference']) . '</a></h2>'
            . '<p><strong>' . lol_html_escape($label) . '</strong> · ' . lol_html_escape((string)$conversation['topic']) . '</p>'
            . '<p>Response: ' . lol_html_escape((string)$conversation['response_method']) . '</p>'
            . '<p class="message-meta">Last activity: ' . lol_html_escape((string)$conversation['last_activity_at']) . '</p>'
            . '</article>';
    }
    echo '</div>';
}

lol_admin_footer();
