<?php
declare(strict_types=1);

require dirname(__DIR__) . '/api/messages/_bootstrap.php';

function expect_true(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$code = lol_new_private_code();
$normalized = lol_normalize_code($code);
expect_true(str_starts_with($code, 'L1O-'), 'private code prefix');
expect_true(strlen($normalized) === 35, 'private code contains 128-bit hex payload plus L1O prefix');
expect_true((bool)preg_match('/^L1O[A-F0-9]{32}$/', $normalized), 'private code character set');

$spaced = implode(' ', str_split($normalized, 5));
expect_true(lol_code_hash($code) === lol_code_hash($spaced), 'code hashing ignores separators');

$contact = 'visitor@example.org';
$cipher = lol_encrypt_contact($contact);
expect_true(is_string($cipher) && $cipher !== '' && $cipher !== $contact, 'contact details are encrypted');
expect_true(lol_decrypt_contact($cipher) === $contact, 'contact encryption round trip');

$clean = lol_text('  <b>Hello</b> <script>alert(1)</script> world  ', 40);
expect_true(!str_contains($clean, '<'), 'HTML tags stripped from message input');
expect_true(strlen(lol_text(str_repeat('x', 100), 20)) === 20, 'input length cap');

$reference = lol_new_public_reference();
expect_true((bool)preg_match('/^L1O-[0-9]{6}-[A-F0-9]{6}$/', $reference), 'public reference format');

echo "Private-message pure-function smoke tests passed.\n";
