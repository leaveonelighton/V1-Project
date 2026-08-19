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

    $close = $pdo->prepare(
        "UPDATE conversations
         SET status = 'closed', closed_at = UTC_TIMESTAMP()
         WHERE status = 'open'
           AND last_activity_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? DAY)"
    );
    $close->execute([$inactiveDays]);
    $closedCount = $close->rowCount();

    $delete = $pdo->prepare(
        "DELETE FROM conversations
         WHERE status = 'closed'
           AND closed_at IS NOT NULL
           AND closed_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? DAY)"
    );
    $delete->execute([$deleteDays]);
    $deletedCount = $delete->rowCount();

    $purgeRates = $pdo->prepare(
        "DELETE FROM rate_events
         WHERE created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 DAY)"
    );
    $purgeRates->execute();
    $rateCount = $purgeRates->rowCount();

    $pdo->commit();

    echo sprintf(
        "Private-message retention complete. Closed: %d; deleted: %d; rate events purged: %d\n",
        $closedCount,
        $deletedCount,
        $rateCount
    );
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Private-message retention failed.\n");
    exit(1);
}
