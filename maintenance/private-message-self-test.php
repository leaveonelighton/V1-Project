<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require dirname(__DIR__) . '/api/messages/_bootstrap.php';

try {
    $pdo = lol_db();
    $pdo->query('SELECT 1')->fetchColumn();

    $sample = 'phase1-test';
    $encrypted = lol_encrypt_contact($sample);
    $decrypted = lol_decrypt_contact($encrypted);
    if (!is_string($encrypted) || $encrypted === '' || $decrypted !== $sample) {
        throw new RuntimeException('Encryption test failed.');
    }

    $code = lol_new_private_code();
    if (strlen(lol_code_hash($code)) !== 64) {
        throw new RuntimeException('Private-code test failed.');
    }

    echo "DATABASE OK\nENCRYPTION OK\nPRIVATE CODE OK\n";
} catch (Throwable $e) {
    fwrite(STDERR, "PRIVATE MESSAGE SELF-TEST FAILED\n");
    exit(1);
}
