<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/messages/_bootstrap.php';

const LB_CONSENT_VERSION = '2026-10-23';
const LB_PERSPECTIVES = ['received' => 'Kindness received', 'witnessed' => 'Kindness witnessed', 'offered' => 'Kindness offered'];

function lb_enabled(): bool
{
    try {
        return (lol_config()['light_board_enabled'] ?? false) === true
            || getenv('LOLO_LIGHT_BOARD_ENABLED') === '1';
    } catch (Throwable $e) {
        return false;
    }
}

function lb_field(array $input, string $key, int $max): string
{
    if (isset($input[$key]) && !is_string($input[$key])) {
        throw new RuntimeException('Please check the form and try again.');
    }
    $value = trim(str_replace(["\r\n", "\r"], "\n", (string)($input[$key] ?? '')));
    if (!preg_match('//u', $value) || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
        throw new RuntimeException('Please use plain text in your story.');
    }
    $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    if ($length > $max) {
        throw new RuntimeException('Please shorten your story to fit the form.');
    }
    return $value;
}

function lb_validate(array $input): array
{
    $perspective = lb_field($input, 'perspective', 20);
    $story = lb_field($input, 'story', 2500);
    $reflection = lb_field($input, 'reflection', 500);
    if (!array_key_exists($perspective, LB_PERSPECTIVES)) {
        throw new RuntimeException('Choose whether you received, witnessed, or offered kindness.');
    }
    if (strlen($story) < 10) {
        throw new RuntimeException('Please add a little more about what happened.');
    }
    if (($input['website_consent'] ?? '') !== 'yes') {
        throw new RuntimeException('Please confirm anonymous website publication, or use private Contact instead.');
    }
    return [$perspective, $story, $reflection, ($input['reuse_consent'] ?? '') === 'yes'];
}

function lb_flags(string $text): array
{
    $flags = [];
    if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text)) $flags[] = 'possible email';
    if (preg_match('/(?:\+?\d[\d(). -]{6,}\d)/', $text)) $flags[] = 'possible phone or identifier';
    if (preg_match('~https?://|www\.|<[^>]+>~i', $text)) $flags[] = 'link or markup';
    return $flags;
}

function lb_event(PDO $pdo, int $id, string $action): void
{
    $pdo->prepare('INSERT INTO light_story_events (story_id, event_type, created_at) VALUES (?, ?, ?)')
        ->execute([$id, $action, gmdate('Y-m-d H:i:s')]);
}

function lb_submit(PDO $pdo, array $input): array
{
    [$perspective, $story, $reflection, $reuse] = lb_validate($input);
    $receipt = lol_new_private_code();
    $publicId = bin2hex(random_bytes(12));
    $now = gmdate('Y-m-d H:i:s');
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO light_stories (public_id, receipt_hash, perspective, story_ciphertext, reflection_ciphertext, website_consent, reuse_consent, consent_version, state, review_flags, revision, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?, 1, ?, ?)')
            ->execute([$publicId, lol_code_hash($receipt), $perspective, lol_encrypt_contact($story), lol_encrypt_contact($reflection), (int)$reuse, LB_CONSENT_VERSION, 'pending', implode(', ', lb_flags($story . ' ' . $reflection)), $now, $now]);
        $id = (int)$pdo->lastInsertId();
        lb_event($pdo, $id, 'submitted');
        $pdo->commit();
        return ['receipt' => $receipt, 'state' => 'pending'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function lb_lookup(PDO $pdo, string $receipt): array
{
    if (!preg_match('/^L1O[A-F0-9]{32}$/', lol_normalize_code($receipt))) {
        throw new RuntimeException('We could not find a story with that private code.');
    }
    $q = $pdo->prepare('SELECT * FROM light_stories WHERE receipt_hash = ?');
    $q->execute([lol_code_hash($receipt)]);
    $row = $q->fetch();
    if (!$row) throw new RuntimeException('We could not find a story with that private code.');
    return $row;
}

function lb_private_status(array $row): array
{
    return ['state' => $row['state'], 'review_note' => $row['review_note'] ?? '',
        'public_url' => $row['state'] === 'approved' ? '/light-board/story.php?id=' . $row['public_id'] : null];
}

function lb_withdraw(PDO $pdo, array $row): void
{
    $pdo->beginTransaction();
    try {
        $q = $pdo->prepare('UPDATE light_stories SET state = ?, public_text = NULL, public_reflection = NULL, updated_at = ?, revision = revision + 1 WHERE id = ? AND revision = ?');
        $q->execute(['withdrawn', gmdate('Y-m-d H:i:s'), $row['id'], $row['revision']]);
        if ($q->rowCount() !== 1) throw new RuntimeException('This story changed. Check its status again.');
        lb_event($pdo, (int)$row['id'], 'withdrawn_by_author');
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function lb_moderate(PDO $pdo, array $row, string $action, array $input): void
{
    if (!in_array($action, ['approve', 'clarify', 'decline', 'unpublish'], true)) {
        throw new RuntimeException('Choose a review action.');
    }
    if ($row['state'] === 'withdrawn') throw new RuntimeException('A withdrawn story cannot be republished.');
    if ($action === 'unpublish' && $row['state'] !== 'approved') throw new RuntimeException('This story is not published.');
    if ($action !== 'unpublish' && !in_array($row['state'], ['pending', 'needs_changes'], true)) {
        throw new RuntimeException('Return the story to review before editing it.');
    }
    // Unpublishing keeps reviewed wording privately so it can be reviewed again
    // even if the encrypted original has already aged out under retention.
    $text = $action === 'unpublish' ? $row['public_text'] : null;
    $reflection = $action === 'unpublish' ? $row['public_reflection'] : null;
    $note = lb_field($input, 'review_note', 1000);
    if ($action === 'approve') {
        if (($input['review_confirmed'] ?? '') !== 'yes' || (int)$row['website_consent'] !== 1) {
            throw new RuntimeException('Confirm the privacy and meaning review before approval.');
        }
        $text = lb_field($input, 'public_text', 2500);
        $reflection = lb_field($input, 'public_reflection', 500);
        if (strlen($text) < 10 || lb_flags($text . ' ' . $reflection)) {
            throw new RuntimeException('Review the public wording: remove links, markup, and possible contact details.');
        }
    }
    if ($action === 'clarify' && $note === '') throw new RuntimeException('Explain what needs clarification.');
    $state = ['approve' => 'approved', 'clarify' => 'needs_changes', 'decline' => 'declined', 'unpublish' => 'pending'][$action];
    $now = gmdate('Y-m-d H:i:s');
    $pdo->beginTransaction();
    try {
        $q = $pdo->prepare('UPDATE light_stories SET state = ?, public_text = ?, public_reflection = ?, review_note = ?, published_at = ?, updated_at = ?, revision = revision + 1 WHERE id = ? AND revision = ?');
        $q->execute([$state, $text, $reflection, $note, $action === 'approve' ? $now : null, $now, $row['id'], $row['revision']]);
        if ($q->rowCount() !== 1) throw new RuntimeException('This story changed during review. Reload before trying again.');
        lb_event($pdo, (int)$row['id'], $action);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function lb_public_item(array $row): array
{
    return ['id' => $row['public_id'], 'perspective' => $row['perspective'],
        'label' => LB_PERSPECTIVES[$row['perspective']], 'text' => $row['public_text'],
        'reflection' => $row['public_reflection'] ?? '',
        'published_at' => str_replace(' ', 'T', $row['published_at']) . 'Z',
        'url' => '/light-board/story.php?id=' . $row['public_id']];
}

function lb_feed(PDO $pdo, string $perspective = '', string $cursor = '', int $limit = 12): array
{
    if ($perspective !== '' && !array_key_exists($perspective, LB_PERSPECTIVES)) throw new RuntimeException('Unknown story filter.');
    $limit = max(1, min(24, $limit));
    $where = "state = 'approved' AND website_consent = 1";
    $params = [];
    if ($perspective !== '') { $where .= ' AND perspective = ?'; $params[] = $perspective; }
    if ($cursor !== '') {
        if (!preg_match('/^[a-f0-9]{24}$/', $cursor)) throw new RuntimeException('Invalid story cursor.');
        $q = $pdo->prepare("SELECT published_at FROM light_stories WHERE public_id = ? AND state = 'approved' AND website_consent = 1");
        $q->execute([$cursor]);
        $date = $q->fetchColumn();
        if (!$date) return ['items' => [], 'next_cursor' => null];
        $where .= ' AND (published_at < ? OR (published_at = ? AND public_id < ?))';
        array_push($params, $date, $date, $cursor);
    }
    $q = $pdo->prepare('SELECT public_id, perspective, public_text, public_reflection, published_at FROM light_stories WHERE ' . $where . ' ORDER BY published_at DESC, public_id DESC LIMIT ' . ($limit + 1));
    $q->execute($params);
    $rows = $q->fetchAll();
    $hasMore = count($rows) > $limit;
    $rows = array_slice($rows, 0, $limit);
    return ['items' => array_map('lb_public_item', $rows), 'next_cursor' => $hasMore ? end($rows)['public_id'] : null];
}
