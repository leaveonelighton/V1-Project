<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/api/messages/_bootstrap.php';

function lol_require_admin(): void
{
    $config = lol_config();
    $expectedUser = (string)($config['admin_user'] ?? '');
    $expectedHash = (string)($config['admin_password_hash'] ?? '');
    $providedUser = (string)($_SERVER['PHP_AUTH_USER'] ?? '');
    $providedPass = (string)($_SERVER['PHP_AUTH_PW'] ?? '');

    $valid = $expectedUser !== ''
        && $expectedHash !== ''
        && hash_equals($expectedUser, $providedUser)
        && password_verify($providedPass, $expectedHash);

    if (!$valid) {
        lol_security_headers(false);
        header('WWW-Authenticate: Basic realm="Leave One Light On Private Messages"');
        header('HTTP/1.1 401 Unauthorized');
        header('Cache-Control: no-store, private');
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Authentication required.';
        exit;
    }
}

function lol_admin_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('lolo_admin');
        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'cookie_samesite' => 'Strict',
            'use_strict_mode' => true,
        ]);
    }
}

function lol_csrf_token(): string
{
    lol_admin_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return (string)$_SESSION['csrf_token'];
}

function lol_verify_csrf(): void
{
    lol_admin_session();
    $provided = (string)($_POST['csrf_token'] ?? '');
    $expected = (string)($_SESSION['csrf_token'] ?? '');
    if ($provided === '' || $expected === '' || !hash_equals($expected, $provided)) {
        throw new RuntimeException('The form expired. Please return to the conversation and try again.');
    }
}

function lol_admin_header(string $title): void
{
    lol_security_headers(true);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, private');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<meta name="robots" content="noindex,nofollow,noarchive">'
        . '<title>' . lol_html_escape($title) . ' | Private Messages</title>'
        . '<link rel="stylesheet" href="/css/golden-state.css">'
        . '<link rel="stylesheet" href="/css/v3.css">'
        . '<link rel="stylesheet" href="/css/communication.css">'
        . '<link rel="stylesheet" href="/css/admin-messages.css">'
        . '</head><body><main id="main-content"><section class="section-light"><div class="wide">';
}

function lol_admin_footer(): void
{
    echo '</div></section></main></body></html>';
}

lol_require_admin();
lol_admin_session();
