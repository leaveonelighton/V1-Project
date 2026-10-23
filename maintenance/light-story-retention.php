<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/api/light-board/_service.php';
$pdo = lol_db();
$apply = in_array('--apply', $argv, true);
$oldPending = gmdate('Y-m-d H:i:s', time() - 90 * 86400);
$oldClosed = gmdate('Y-m-d H:i:s', time() - 30 * 86400);
$tasks = [
    ["DELETE FROM light_stories WHERE state IN ('pending','needs_changes') AND updated_at < ?", [$oldPending], 'Old pending/clarification entries'],
    ["DELETE FROM light_stories WHERE state IN ('declined','withdrawn') AND updated_at < ?", [$oldClosed], 'Old declined/withdrawn entries'],
    ["UPDATE light_stories SET story_ciphertext = '', reflection_ciphertext = NULL, review_note = NULL WHERE state = 'approved' AND published_at < ? AND (story_ciphertext <> '' OR reflection_ciphertext IS NOT NULL OR review_note IS NOT NULL)", [$oldClosed], 'Published private originals/notes'],
    ['DELETE FROM rate_events WHERE created_at < ?', [gmdate('Y-m-d H:i:s', time() - 2 * 86400)], 'Expired abuse-prevention records'],
];
echo $apply ? "Applying story retention.\n" : "Dry run only. Add --apply after reviewing the policy and backup.\n";
foreach ($tasks as [$sql, $params, $label]) {
    if ($apply) {
        $q = $pdo->prepare($sql); $q->execute($params); $count = $q->rowCount();
    } else {
        $where = substr($sql, strpos($sql, ' WHERE ') + 7);
        $table = str_starts_with($sql, 'DELETE FROM rate_events') ? 'rate_events' : 'light_stories';
        $q = $pdo->prepare('SELECT COUNT(*) FROM ' . $table . ' WHERE ' . $where);
        $q->execute($params); $count = (int)$q->fetchColumn();
    }
    echo $label . ': ' . $count . "\n";
}
