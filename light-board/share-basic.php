<?php
declare(strict_types=1);
require dirname(__DIR__) . '/api/light-board/_service.php';
lol_security_headers(true);
header('Cache-Control: no-store, private');
header('Content-Type: text/html; charset=utf-8');
$ready = false;
try {
    if (lb_enabled()) { lb_feed(lol_db(), '', '', 1); $ready = true; }
} catch (Throwable $e) { $ready = false; }
$page = file_get_contents(__DIR__ . '/share.html');
if ($page === false) { http_response_code(503); exit('Story submissions are temporarily unavailable.'); }
// This fallback uses the same form and server validation, without JavaScript.
$page = str_replace('<form class="light-form" hidden', '<form class="light-form"' . ($ready ? '' : ' hidden'), $page);
$page = preg_replace_callback('~<p[^>]*data-intake-status[^>]*>.*?</p>~s', fn() => '<p role="status">' . ($ready ? 'Every story waits for human review before publication.' : 'Community story submissions are in preparation. Please return soon, or use private Contact.') . '</p>', $page);
$page = preg_replace('~<noscript>.*?</noscript>~s', '', $page);
echo $page;
