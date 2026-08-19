<?php
declare(strict_types=1);

function lol_config(): array
{
    static $config = null;
    if (is_array($config)) {
        return $config;
    }

    $documentRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $candidates = [];
    if ($documentRoot !== '') {
        $candidates[] = dirname($documentRoot) . '/private-config/leave-one-light-on-messages.php';
    }
    $candidates[] = dirname(__DIR__, 2) . '/config/private-messages.local.php';

    foreach ($candidates as $path) {
        if (is_file($path)) {
            $loaded = require $path;
            if (is_array($loaded)) {
                $config = $loaded;
                return $config;
            }
        }
    }

    $env = [
        'db_host' => getenv('LOLO_DB_HOST') ?: '',
        'db_name' => getenv('LOLO_DB_NAME') ?: '',
        'db_user' => getenv('LOLO_DB_USER') ?: '',
        'db_pass' => getenv('LOLO_DB_PASS') ?: '',
        'crypto_key_b64' => getenv('LOLO_CRYPTO_KEY_B64') ?: '',
        'rate_hmac_secret' => getenv('LOLO_RATE_HMAC_SECRET') ?: '',
        'admin_user' => getenv('LOLO_ADMIN_USER') ?: '',
        'admin_password_hash' => getenv('LOLO_ADMIN_PASSWORD_HASH') ?: '',
        'inactive_close_days' => 180,
        'closed_delete_days' => 90,
    ];

    if ($env['db_host'] && $env['db_name'] && $env['db_user'] && $env['rate_hmac_secret']) {
        $config = $env;
        return $config;
    }

    throw new RuntimeException('Private messaging is not configured on this environment.');
}

function lol_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = lol_config();
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $config['db_host'],
        $config['db_name']
    );

    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function lol_security_headers(bool $html = false): void
{
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('X-Frame-Options: DENY');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    if ($html) {
        header("Content-Security-Policy: default-src 'self'; style-src 'self'; img-src 'self' data:; script-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
    }
}

function lol_wants_json(): bool
{
    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    $requestedWith = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    return str_contains($accept, 'application/json') || $requestedWith === 'fetch';
}

function lol_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    lol_security_headers(false);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function lol_html_escape(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function lol_text(mixed $value, int $maxLength): string
{
    $text = trim((string)$value);
    $text = strip_tags($text);
    if (function_exists('mb_substr')) {
        return mb_substr($text, 0, $maxLength, 'UTF-8');
    }
    return substr($text, 0, $maxLength);
}

function lol_normalize_code(string $code): string
{
    $normalized = strtoupper($code);
    return (string)preg_replace('/[^A-Z0-9]/', '', $normalized);
}

function lol_code_hash(string $code): string
{
    return hash('sha256', lol_normalize_code($code));
}

function lol_new_private_code(): string
{
    $hex = strtoupper(bin2hex(random_bytes(16))); // 128 bits
    return 'L1O-' . implode('-', str_split($hex, 4));
}

function lol_new_public_reference(): string
{
    return 'L1O-' . gmdate('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function lol_crypto_key(): string
{
    $config = lol_config();
    $decoded = base64_decode((string)($config['crypto_key_b64'] ?? ''), true);
    if ($decoded === false || strlen($decoded) !== 32) {
        throw new RuntimeException('Contact-detail encryption is not configured correctly.');
    }
    return $decoded;
}

function lol_encrypt_contact(?string $plainText): ?string
{
    $plainText = trim((string)$plainText);
    if ($plainText === '') {
        return null;
    }

    $iv = random_bytes(12);
    $tag = '';
    $cipherText = openssl_encrypt(
        $plainText,
        'aes-256-gcm',
        lol_crypto_key(),
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );
    if ($cipherText === false) {
        throw new RuntimeException('Unable to protect contact details.');
    }

    return base64_encode($iv . $tag . $cipherText);
}

function lol_decrypt_contact(?string $encoded): ?string
{
    if ($encoded === null || $encoded === '') {
        return null;
    }

    $raw = base64_decode($encoded, true);
    if ($raw === false || strlen($raw) < 29) {
        return null;
    }

    $iv = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $cipherText = substr($raw, 28);
    $plainText = openssl_decrypt(
        $cipherText,
        'aes-256-gcm',
        lol_crypto_key(),
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    return $plainText === false ? null : $plainText;
}

function lol_rate_key(): string
{
    $config = lol_config();
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return hash_hmac('sha256', $ip, (string)$config['rate_hmac_secret']);
}

function lol_enforce_rate_limit(PDO $pdo, string $eventType, int $limit, int $windowSeconds): void
{
    $rateKey = lol_rate_key();
    $cutoff = gmdate('Y-m-d H:i:s', time() - $windowSeconds);

    $delete = $pdo->prepare('DELETE FROM rate_events WHERE created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 DAY)');
    $delete->execute();

    $count = $pdo->prepare(
        'SELECT COUNT(*) FROM rate_events WHERE rate_key = ? AND event_type = ? AND created_at >= ?'
    );
    $count->execute([$rateKey, $eventType, $cutoff]);
    if ((int)$count->fetchColumn() >= $limit) {
        throw new RuntimeException('Too many requests. Please wait and try again.');
    }

    $insert = $pdo->prepare('INSERT INTO rate_events (rate_key, event_type) VALUES (?, ?)');
    $insert->execute([$rateKey, $eventType]);
}

function lol_audit(PDO $pdo, ?int $conversationId, string $eventType): void
{
    $stmt = $pdo->prepare('INSERT INTO audit_events (conversation_id, event_type) VALUES (?, ?)');
    $stmt->execute([$conversationId, $eventType]);
}

function lol_render_shell(string $title, string $bodyHtml): never
{
    lol_security_headers(true);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    $safeTitle = lol_html_escape($title);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<meta name="robots" content="noindex,nofollow">'
       . '<title>' . $safeTitle . ' | Leave One Light On</title>'
       . '<link rel="stylesheet" href="/css/golden-state.css">'
       . '<link rel="stylesheet" href="/css/v3.css">'
       . '<link rel="stylesheet" href="/css/communication.css">'
       . '</head><body><main id="main-content"><section class="section-light"><div class="narrow">'
       . $bodyHtml
       . '</div></section></main></body></html>';
    exit;
}
