<?php
declare(strict_types=1);
require __DIR__ . '/_service.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') lol_json(['ok' => false, 'error' => 'GET required.'], 405);
if (!lb_enabled()) lol_json(['ok' => true, 'accepting' => false, 'items' => [], 'next_cursor' => null]);
try {
    $data = lb_feed(lol_db(), lb_field($_GET, 'perspective', 20), lb_field($_GET, 'cursor', 24));
    lol_json(['ok' => true, 'accepting' => true] + $data);
} catch (Throwable $e) {
    lol_json(['ok' => false, 'error' => 'Community stories are temporarily unavailable.'], 503);
}
