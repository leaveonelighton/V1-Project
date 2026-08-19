<?php
declare(strict_types=1);
require __DIR__ . '/_admin.php';

$pdo = lol_db();
$status = (string)($_GET['status'] ?? 'open');
if (!in_array($status, ['open', 'closed', 'all'], true)) {
    $status = 'open';
}

$countRows = $pdo->query('SELECT status, COUNT(*) AS total FROM conversations GROUP BY status')->fetchAll();
$counts = ['open' => 0, 'closed' => 0, 'all' => 0];
foreach ($countRows as $row) {
    $key = (string)$row['status'];
    $total = (int)$row['total'];
    if (array_key_exists($key, $counts)) {
        $counts[$key] = $total;
    }
    $counts['all'] += $total;
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

$methodLabels = [
    'private' => 'Private website reply',
    'email' => 'Email',
    'text' => 'Text',
    'phone' => 'Phone',
    'none' => 'No reply requested',
];

lol_admin_header('Private Messages');
echo '<div class="admin-heading"><div><p class="section-label">Protected admin</p><h1>Private Messages</h1><p>Review, answer, and close website conversations.</p></div>'
    . '<nav class="admin-filter-nav" aria-label="Message filters">'
    . '<a' . ($status === 'open' ? ' aria-current="page"' : '') . ' href="?status=open">Open <span>' . $counts['open'] . '</span></a>'
    . '<a' . ($status === 'closed' ? ' aria-current="page"' : '') . ' href="?status=closed">Closed <span>' . $counts['closed'] . '</span></a>'
    . '<a' . ($status === 'all' ? ' aria-current="page"' : '') . ' href="?status=all">All <span>' . $counts['all'] . '</span></a>'
    . '</nav></div>';

if (!$conversations) {
    echo '<div class="admin-empty-state"><h2>No conversations here.</h2><p>No conversations match this filter.</p></div>';
} else {
    echo '<div class="admin-message-list">';
    foreach ($conversations as $conversation) {
        $nickname = trim((string)($conversation['nickname'] ?? ''));
        $label = $nickname !== '' ? $nickname : 'Anonymous visitor';
        $method = (string)$conversation['response_method'];
        $methodLabel = $methodLabels[$method] ?? ucfirst($method);
        echo '<article class="admin-message-card">'
            . '<div class="admin-card-topline"><span class="status-chip status-' . lol_html_escape((string)$conversation['status']) . '">' . lol_html_escape((string)$conversation['status']) . '</span>'
            . '<span class="message-meta">' . lol_html_escape((string)$conversation['last_activity_at']) . '</span></div>'
            . '<h2><a href="conversation.php?id=' . (int)$conversation['id'] . '">' . lol_html_escape((string)$conversation['public_reference']) . '</a></h2>'
            . '<p><strong>' . lol_html_escape($label) . '</strong> · ' . lol_html_escape((string)$conversation['topic']) . '</p>'
            . '<p><strong>Reply:</strong> ' . lol_html_escape($methodLabel) . '</p>'
            . '</article>';
    }
    echo '</div>';
}

lol_admin_footer();
