<?php
declare(strict_types=1);
require dirname(__DIR__) . '/api/light-board/_service.php';

header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
$row = false;
$id = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
try {
    if (lb_enabled() && preg_match('/^[a-f0-9]{24}$/', $id)) {
        $q = lol_db()->prepare("SELECT public_id, perspective, public_text, public_reflection, published_at FROM light_stories WHERE public_id = ? AND state = 'approved' AND website_consent = 1");
        $q->execute([$id]);
        $row = $q->fetch();
    }
} catch (Throwable $e) {
    http_response_code(503);
    header('X-Robots-Tag: noindex, nofollow');
    lol_render_shell('Story unavailable', '<h1>This story is temporarily unavailable.</h1><p><a href="/light-board/">Return to the Light Board</a></p>');
}
if (!$row) {
    http_response_code(404);
    header('X-Robots-Tag: noindex, nofollow');
    lol_render_shell('Story unavailable', '<h1>This Light is not available.</h1><p>It may have been removed or may not be published.</p><p><a href="/light-board/">Return to the Light Board</a></p>');
}
$item = lb_public_item($row);
$url = 'https://leaveonelighton.org' . $item['url'];
$excerpt = function_exists('mb_substr') ? mb_substr($item['text'], 0, 160, 'UTF-8') : substr($item['text'], 0, 160);
$body = '<section class="light-hero"><div class="light-narrow"><p class="light-kicker">Received Light. Shared Light.</p><h1>A Light On Story</h1><p>' . lol_html_escape($item['label']) . ' · Anonymous community account</p></div></section>'
    . '<section class="light-section"><article class="light-narrow"><p class="light-text">' . lol_html_escape($item['text']) . '</p>';
if ($item['reflection'] !== '') $body .= '<h2>What stayed with me</h2><p class="light-text">' . lol_html_escape($item['reflection']) . '</p>';
$body .= '<p class="light-help">Reviewed for suitability and privacy. This community account has not been independently verified.</p>'
    . '<div class="light-actions"><button type="button" class="light-button" data-share-story>Share this Light</button><a href="/light-board/share.html" class="light-button secondary">Share your own Light</a></div>'
    . '<p role="status" aria-live="polite" data-share-status></p><p class="light-help">Story link: <a href="' . lol_html_escape($url) . '">' . lol_html_escape($url) . '</a></p>'
    . '<div class="light-invitation"><h2>What could this look like in your day?</h2><p>Notice the need. Take one meaningful step.</p></div><p><a href="/light-board/">Return to the Light Board</a> · <a href="/light-board/methodology.html">Review and privacy</a></p></article></section>';
$page = file_get_contents(__DIR__ . '/index.html');
if ($page === false) { http_response_code(503); exit('Story temporarily unavailable.'); }
$page = preg_replace_callback('~<main\b[^>]*>.*?</main>~s', fn() => '<main id="main-content" class="light-experience">' . $body . '</main>', $page);
$page = preg_replace_callback('~<title>.*?</title>~s', fn() => '<title>A Light On Story | Leave One Light On</title>', $page);
$page = preg_replace_callback('~(<link rel="canonical" href=")[^"]*~', fn($m) => $m[1] . lol_html_escape($url), $page);
foreach (['property="og:url"' => $url, 'property="og:title"' => 'A Light On Story | Leave One Light On', 'name="twitter:title"' => 'A Light On Story | Leave One Light On', 'name="description"' => $excerpt, 'property="og:description"' => $excerpt, 'name="twitter:description"' => $excerpt] as $attribute => $value) {
    $page = preg_replace_callback('~(<meta ' . preg_quote($attribute, '~') . ' content=")[^"]*~', fn($m) => $m[1] . lol_html_escape($value), $page);
}
header('Content-Type: text/html; charset=utf-8');
echo $page;
