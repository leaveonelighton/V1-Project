<?php
declare(strict_types=1);
// Existing staging only. Never use this wrapper on production.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require dirname(__DIR__) . '/api/messages/_bootstrap.php';
$report = ['started_at'=>gmdate('c'),'ok'=>false];
try {
    $config = lol_config();
    if (($config['db_name'] ?? '') !== 'u856117160_lolo_stage56' || ($config['db_user'] ?? '') !== 'u856117160_lolo_stage56' || ($config['light_board_enabled'] ?? false) || ($config['gorman_stories_enabled'] ?? false) || getenv('LOLO_LIGHT_BOARD_ENABLED') === '1') throw new RuntimeException('Isolation guard refused.');
    $pdo = lol_db();
    if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== 'u856117160_lolo_stage56') throw new RuntimeException('Isolation guard refused.');
    $argv = [__DIR__ . '/light-story-retention.php', '--apply'];
    ob_start();
    require __DIR__ . '/light-story-retention.php';
    $report['result'] = trim((string)ob_get_clean());
    $report['ok'] = true;
} catch (Throwable $e) {
    if (ob_get_level() > 0) ob_end_clean();
    $report['error'] = 'Staging retention failed; inspect configuration privately.';
}
$report['completed_at'] = gmdate('c');
$reportPath = dirname(__DIR__, 2) . '/private-config/pr56-retention-result.json';
file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
chmod($reportPath, 0600);
echo $report['ok'] ? "Staging retention completed.\n" : "Staging retention failed.\n";
