<?php
declare(strict_types=1);
// In-memory service integration test. No external account, production database, or real submissions.
putenv('LOLO_DB_HOST=unused'); putenv('LOLO_DB_NAME=unused'); putenv('LOLO_DB_USER=unused');
putenv('LOLO_RATE_HMAC_SECRET=ci-only-do-not-use-in-production');
putenv('LOLO_CRYPTO_KEY_B64=' . base64_encode(str_repeat('a', 32)));
require dirname(__DIR__) . '/api/light-board/_service.php';
function check(bool $ok, string $message): void { if (!$ok) throw new RuntimeException('FAIL: ' . $message); }
function rejected(callable $operation, string $message): void {
    try { $operation(); } catch (RuntimeException $e) { return; }
    throw new RuntimeException('FAIL: ' . $message);
}
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys = ON');
$schema = file_get_contents(dirname(__DIR__) . '/docs/light-board-schema.sql');
$schema = str_replace('BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $schema);
$schema = preg_replace('/^\s*KEY .*\n/m', '', $schema);
$schema = preg_replace('/,\s*\)/', ')', $schema);
$schema = preg_replace('/\) ENGINE=InnoDB[^;]+;/', ');', $schema);
$pdo->exec($schema);
$input = ['perspective' => 'received', 'story' => 'A neighbor waited while I found the words. That patience mattered.', 'reflection' => 'I can make time for someone too.', 'website_consent' => 'yes'];
rejected(fn() => lb_submit($pdo, array_diff_key($input, ['website_consent' => true])), 'website consent must be explicit');
rejected(fn() => lb_submit($pdo, array_replace($input, ['perspective' => 'invented'])), 'unknown perspective rejected');
rejected(fn() => lb_submit($pdo, array_replace($input, ['story' => str_repeat('x', 2501)])), 'overlong story rejected instead of silently truncated');
$receipt = lb_submit($pdo, $input + ['state' => 'approved', 'public_text' => 'Injected public text']);
$row = lb_lookup($pdo, $receipt['receipt']);
check($row['state'] === 'pending', 'client cannot choose published state');
check((int)$row['reuse_consent'] === 0, 'secondary reuse permission defaults off');
check($row['story_ciphertext'] !== $input['story'] && lol_decrypt_contact($row['story_ciphertext']) === $input['story'], 'private original encrypted with correct round trip');
check(!str_contains(json_encode($row), $receipt['receipt']), 'raw receipt not stored');
check(lb_feed($pdo)['items'] === [], 'pending never appears in public feed');
$public = ['public_text' => $input['story'], 'public_reflection' => $input['reflection'], 'review_confirmed' => 'yes'];
rejected(fn() => lb_moderate($pdo, $row, 'approve', array_diff_key($public, ['review_confirmed' => true])), 'review confirmation required');
rejected(fn() => lb_moderate($pdo, $row, 'approve', array_replace($public, ['public_text' => 'Call me at visitor@example.org.'])), 'possible contact details cannot be approved');
lb_moderate($pdo, $row, 'clarify', ['review_note' => 'Please leave out the identifying place.']);
check(lb_feed($pdo)['items'] === [], 'clarification never appears publicly');
$row = lb_lookup($pdo, $receipt['receipt']);
check(lb_private_status($row)['review_note'] !== '', 'receipt holder can read clarification note');
lb_moderate($pdo, $row, 'approve', $public);
$items = lb_feed($pdo)['items'];
check(count($items) === 1 && $items[0]['text'] === $input['story'], 'human approval publishes reviewed wording');
check(array_keys($items[0]) === ['id','perspective','label','text','reflection','published_at','url'], 'public projection excludes private fields');
$row = lb_lookup($pdo, $receipt['receipt']);
rejected(fn() => lb_moderate($pdo, $row, 'approve', $public), 'published story cannot be edited without returning to review');
lb_moderate($pdo, $row, 'unpublish', []);
check(lb_feed($pdo)['items'] === [], 'unpublish removes public story');
rejected(fn() => lb_moderate($pdo, $row, 'unpublish', []), 'stale review loses optimistic lock');
$row = lb_lookup($pdo, $receipt['receipt']);
lb_withdraw($pdo, $row);
$row = lb_lookup($pdo, $receipt['receipt']);
check($row['state'] === 'withdrawn' && $row['public_text'] === null, 'author withdrawal clears public wording');
rejected(fn() => lb_moderate($pdo, $row, 'approve', $public), 'withdrawn entry cannot be republished');
rejected(fn() => lb_lookup($pdo, 'not-a-private-code'), 'invalid receipt rejected');
for ($i = 0; $i < 4; $i++) {
    $r = lb_submit($pdo, array_replace($input, ['perspective' => $i === 0 ? 'offered' : 'witnessed', 'reuse_consent' => 'yes']));
    $entry = lb_lookup($pdo, $r['receipt']); lb_moderate($pdo, $entry, 'approve', $public);
}
$first = lb_feed($pdo, 'witnessed', '', 2);
$second = lb_feed($pdo, 'witnessed', $first['next_cursor'], 2);
check(count($first['items']) === 2 && count($second['items']) === 1, 'filter and pagination cover all matching published stories');
check(array_intersect(array_column($first['items'], 'id'), array_column($second['items'], 'id')) === [], 'pagination has no duplicates');
check(count(lb_feed($pdo, 'offered')['items']) === 1, 'perspective filters do not cross categories');
check((int)$pdo->query('SELECT COUNT(*) FROM light_story_events')->fetchColumn() > 0, 'review and withdrawal actions audited');
echo "Light-story service integration tests passed.\n";

// Synthetic Gorman lane: same queue, encryption, moderation and withdrawal.
check(!lb_gorman_enabled(), 'Gorman intake defaults off');
$gorman = ['perspective' => 'gorman', 'story' => 'Synthetic test account only. This is not a real personal story.', 'attribution_mode' => 'named', 'contributor_name' => 'Test Pseudonym', 'private_contact' => 'synthetic@example.invalid'];
$r = lb_submit($pdo, $gorman); $g = lb_lookup($pdo, $r['receipt']);
check((int)$g['website_consent'] === 0, 'private review permitted without publication consent');
rejected(fn() => lb_moderate($pdo, $g, 'approve', $public), 'private-only Gorman story cannot be published');
check(!str_contains(json_encode($g), 'synthetic@example.invalid'), 'optional contact encrypted');
$details = json_decode(lol_decrypt_contact($g['contributor_ciphertext']), true);
check($details['contact'] === 'synthetic@example.invalid', 'private contact round trip');
rejected(fn() => lb_submit($pdo, array_replace($gorman, ['contributor_name' => ''])), 'named attribution needs author-provided name');
rejected(fn() => lb_submit($pdo, array_replace($gorman, ['private_contact' => 'not email'])), 'malformed optional contact rejected');
$r = lb_submit($pdo, $gorman + ['website_consent' => 'yes', 'state' => 'approved']); $g = lb_lookup($pdo, $r['receipt']);
check($g['state'] === 'pending', 'Gorman client cannot auto-publish');
lb_moderate($pdo, $g, 'approve', $public + ['public_attribution' => 'Injected name']);
$g = lb_lookup($pdo, $r['receipt']); $item = lb_public_item($g);
check($item['attribution'] === 'Test Pseudonym', 'only author-chosen attribution published');
check(!str_contains(json_encode($item), 'synthetic@example.invalid') && !isset($item['contributor_ciphertext']), 'public projection excludes private contributor data');
check(!in_array($g['public_id'], array_column(lb_feed($pdo)['items'], 'id')), 'Gorman accounts excluded from general Light Board feed');
rejected(fn() => lb_feed($pdo, 'gorman'), 'general feed cannot expose Gorman lane');
lb_withdraw($pdo, $g); $g = lb_lookup($pdo, $r['receipt']);
check($g['public_attribution'] === null && $g['public_text'] === null, 'withdrawal clears public story and attribution');
rejected(fn() => lb_moderate($pdo, $g, 'approve', $public), 'withdrawn Gorman story stays withdrawn');
$r = lb_submit($pdo, array_replace($gorman, ['attribution_mode' => 'anonymous', 'website_consent' => 'yes'])); $g = lb_lookup($pdo, $r['receipt']);
lb_moderate($pdo, $g, 'approve', $public); $g = lb_lookup($pdo, $r['receipt']);
check(lb_public_item($g)['attribution'] === 'Anonymous', 'private supplied name stays private for anonymous attribution');
echo "Gorman consent, encryption, attribution, segregation and withdrawal tests passed.\n";
