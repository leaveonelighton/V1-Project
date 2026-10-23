<?php
declare(strict_types=1);
require __DIR__ . '/_service.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') lol_json(['ok' => false, 'error' => 'POST required.'], 405);
// Status and withdrawal remain available when new submissions are paused.
try {
    $pdo = lol_db();
    lol_enforce_rate_limit($pdo, 'light_status', 30, 3600);
    $row = lb_lookup($pdo, lb_field($_POST, 'receipt', 80));
    if (($_POST['action'] ?? 'status') === 'withdraw') {
        lb_withdraw($pdo, $row);
        $row['state'] = 'withdrawn';
    }
    $result = lb_private_status($row);
    if (lol_wants_json()) lol_json(['ok' => true] + $result);
    $withdraw = $row['state'] === 'withdrawn' ? '' : '<form method="post" action="/api/light-board/status.php"><input type="hidden" name="receipt" value="' . lol_html_escape((string)$_POST['receipt']) . '"><input type="hidden" name="action" value="withdraw"><p>Withdrawal removes this entry from the public board and prevents future publication. Copies already shared elsewhere may remain.</p><button type="submit">Withdraw this story</button></form>';
    lol_render_shell('Your story', '<h1>Story status: ' . lol_html_escape($result['state']) . '</h1><p>' . nl2br(lol_html_escape($result['review_note'])) . '</p>' . $withdraw . '<p><a href="/light-board/my-story.html">Return to Check my story</a></p>');
} catch (Throwable $e) {
    $message = $e instanceof RuntimeException && !($e instanceof PDOException) ? $e->getMessage() : 'We could not check your story. Please try again later.';
    if (lol_wants_json()) lol_json(['ok' => false, 'error' => $message], 400);
    lol_render_shell('Story status unavailable', '<h1>We could not check your story.</h1><p>' . lol_html_escape($message) . '</p><p><a href="/light-board/my-story.html">Try again</a></p>');
}
