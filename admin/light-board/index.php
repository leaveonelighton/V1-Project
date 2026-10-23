<?php
declare(strict_types=1);
require dirname(__DIR__) . '/messages/_admin.php';
require dirname(__DIR__, 2) . '/api/light-board/_service.php';

$pdo = lol_db();
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        lol_verify_csrf();
        $q = $pdo->prepare('SELECT * FROM light_stories WHERE id = ?');
        $q->execute([(int)($_POST['id'] ?? 0)]);
        $row = $q->fetch();
        if (!$row || (int)$row['revision'] !== (int)($_POST['revision'] ?? 0)) throw new RuntimeException('This story changed. Reload it before reviewing.');
        lb_moderate($pdo, $row, lb_field($_POST, 'action', 20), $_POST);
        header('Location: /admin/light-board/?saved=1', true, 303);
        exit;
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException && !($e instanceof PDOException) ? $e->getMessage() : 'Review could not be saved.';
    }
}
lol_admin_header('Light Board Review');
echo '<h1>Light Board Review</h1><p><a href="/admin/light-board/">Review queue</a> · <a href="/admin/messages/">Private messages</a> · <a href="/light-board/">Public board</a></p>';
if (!lb_enabled()) echo '<p class="privacy-note">New submissions and the public feed are paused in this environment.</p>';
if ($error !== '') echo '<p role="alert">' . lol_html_escape($error) . '</p>';
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id > 0) {
    $q = $pdo->prepare('SELECT * FROM light_stories WHERE id = ?');
    $q->execute([$id]);
    $row = $q->fetch();
    if (!$row) { echo '<h2>Story not found.</h2>'; lol_admin_footer(); exit; }
    $original = lol_decrypt_contact($row['story_ciphertext']) ?? '';
    $reflection = lol_decrypt_contact($row['reflection_ciphertext']) ?? '';
    echo '<h2>' . lol_html_escape(LB_PERSPECTIVES[$row['perspective']]) . '</h2><p><strong>Status:</strong> ' . lol_html_escape($row['state']) . '</p>'
       . '<p><strong>Newsletter/social permission:</strong> ' . ((int)$row['reuse_consent'] === 1 ? 'Yes' : 'No — website only') . '</p>'
       . '<p><strong>Automatic review flags:</strong> ' . lol_html_escape($row['review_flags'] ?: 'None. Human review is still required.') . '</p>'
       . '<h3>Private original</h3><p style="white-space:pre-wrap">' . lol_html_escape($original ?: 'Original removed under retention policy.') . '</p>'
       . '<p style="white-space:pre-wrap">' . lol_html_escape($reflection) . '</p>';
    if ($row['state'] !== 'withdrawn' && $row['state'] !== 'declined') {
        echo '<form method="post"><input type="hidden" name="csrf_token" value="' . lol_html_escape(lol_csrf_token()) . '">'
           . '<input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="revision" value="' . (int)$row['revision'] . '">';
        if ($row['state'] === 'approved') {
            echo '<p>Unpublishing removes the public story immediately and returns it to review.</p><button class="button" name="action" value="unpublish">Unpublish and return to review</button>';
        } else {
            echo '<label for="public-text">Public story</label><textarea id="public-text" name="public_text" rows="10" maxlength="2500">' . lol_html_escape($row['public_text'] ?: $original) . '</textarea>'
               . '<label for="public-reflection">Public reflection (optional)</label><textarea id="public-reflection" name="public_reflection" rows="4" maxlength="500">' . lol_html_escape($row['public_reflection'] ?: $reflection) . '</textarea>'
               . '<label for="review-note">Private note to the author</label><textarea id="review-note" name="review_note" rows="3" maxlength="1000">' . lol_html_escape($row['review_note'] ?? '') . '</textarea>'
               . '<label><input type="checkbox" name="review_confirmed" value="yes"> I reviewed this for privacy, dignity, suitability, and faithful meaning. I removed identifying details and made only light edits.</label>'
               . '<div class="button-row"><button class="button" name="action" value="approve">Approve anonymous publication</button><button class="button" name="action" value="clarify">Request clarification</button><button class="button" name="action" value="decline">Decline publication</button></div>';
        }
        echo '</form>';
    }
} else {
    $state = (string)($_GET['state'] ?? 'pending');
    if (!in_array($state, ['pending', 'needs_changes', 'approved', 'declined', 'withdrawn'], true)) $state = 'pending';
    echo '<nav aria-label="Story review status">';
    foreach (['pending' => 'Pending', 'needs_changes' => 'Clarification', 'approved' => 'Published', 'declined' => 'Declined', 'withdrawn' => 'Withdrawn'] as $key => $label) echo '<a href="?state=' . $key . '">' . $label . '</a> ';
    echo '</nav>';
    $q = $pdo->prepare('SELECT id, perspective, created_at, review_flags FROM light_stories WHERE state = ? ORDER BY created_at ASC LIMIT 100');
    $q->execute([$state]);
    $rows = $q->fetchAll();
    if (!$rows) echo '<p>No stories in this queue.</p>';
    foreach ($rows as $row) echo '<article class="admin-message-card"><h2><a href="?id=' . (int)$row['id'] . '">' . lol_html_escape(LB_PERSPECTIVES[$row['perspective']]) . '</a></h2><p>' . lol_html_escape($row['created_at']) . '</p><p>' . lol_html_escape($row['review_flags']) . '</p></article>';
    if (count($rows) === 100) echo '<p>Showing the oldest 100 stories. Review these to reveal later submissions.</p>';
}
lol_admin_footer();
