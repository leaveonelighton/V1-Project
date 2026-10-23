<?php
declare(strict_types=1);
require __DIR__ . '/_service.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') lol_json(['ok' => false, 'error' => 'POST required.'], 405);
if (!lb_enabled()) lol_json(['ok' => false, 'error' => 'Story submissions are not open yet. Please return soon.'], 503);
try {
    $pdo = lol_db();
    lol_enforce_rate_limit($pdo, 'light_submit', 6, 3600);
    if (trim((string)($_POST['website'] ?? '')) !== '') throw new RuntimeException('Unable to submit this story.');
    $started = (int)($_POST['started_at'] ?? 0);
    if ($started > 0 && time() - $started < 2) throw new RuntimeException('Take a moment to review your story before sending.');
    $result = lb_submit($pdo, $_POST);
    if (lol_wants_json()) lol_json(['ok' => true] + $result, 201);
    lol_render_shell('Story received', '<h1>Your story is waiting for review.</h1><p>It is not public. Save this private code to check its status or withdraw it:</p><p><code>' . lol_html_escape($result['receipt']) . '</code></p><p>We cannot recover a lost code. Publication is not guaranteed.</p><p><a href="/light-board/my-story.html">Check my story</a></p>');
} catch (Throwable $e) {
    $message = $e instanceof RuntimeException && !($e instanceof PDOException) ? $e->getMessage() : 'We could not receive your story. Please try again later.';
    if (lol_wants_json()) lol_json(['ok' => false, 'error' => $message], 400);
    lol_render_shell('Story not sent', '<h1>Your story was not sent.</h1><p>' . lol_html_escape($message) . '</p><p><a href="/light-board/share.html">Return to Share a Light</a></p>');
}
