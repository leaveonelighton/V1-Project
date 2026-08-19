<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/api/messages/_bootstrap.php';

try {
    $config = lol_config();
    $pdo = lol_db();
    $inactiveDays = max(1, (int)($config['inactive_close_days'] ?? 180));
    $deleteDays = max(1, (int)($config['closed_delete_days'] ?? 90));

    $pdo->beginTransaction();

    $closeSql = sprintf(
        "UPDATE conversations
         SET status = 'closed', closed_at = UTC_TIMESTAMP()
         WHERE status = 'open'
           AND last_activity_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY)",
        $inactiveDays
    );
    $closedCount = $pdo->exec($closeSql);

    $deleteSql = sprintf(
        "DELETE FROM conversations
         WHERE status = 'closed'
           AND closed_at IS NOT NULL
           AND closed_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY)",
        $deleteDays
    );
    $deletedCount = $pdo->exec($deleteSql);

    $rateCount = $pdo->exec(
        "DELETE FROM rate_events
         WHERE created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 DAY)"
    );

    $pdo->commit();

    echo sprintf(
        "Private-message retention complete. Closed: %d; deleted: %d; rate events purged: %d\n",
        (int)$closedCount,
        (int)$deletedCount,
        (int)$rateCount
    );
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Private-message retention failed.\n");
    exit(1);
}
