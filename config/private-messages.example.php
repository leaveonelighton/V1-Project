<?php
/**
 * Example only. Do not commit real credentials or secrets.
 *
 * Production recommendation:
 *   store the real config outside public_html at
 *   ../private-config/leave-one-light-on-messages.php
 *
 * Local development fallback:
 *   copy this file to config/private-messages.local.php
 *   (that path is ignored by Git).
 */
return [
    'db_host' => 'localhost',
    'db_name' => 'leave_one_light_on_messages',
    'db_user' => 'replace_me',
    'db_pass' => 'replace_me',

    // Base64-encoded 32 random bytes. Used only for optional contact details.
    'crypto_key_b64' => 'replace_with_base64_32_byte_key',

    // Long random secret used to HMAC transient rate-limit identifiers.
    'rate_hmac_secret' => 'replace_with_long_random_secret',

    // Application-level Basic Auth for the protected admin inbox.
    'admin_user' => 'ted',
    // Generate with password_hash('your-long-password', PASSWORD_DEFAULT).
    'admin_password_hash' => '$2y$10$replace_with_password_hash',

    // Retention defaults used by maintenance tooling/documentation.
    'inactive_close_days' => 180,
    'closed_delete_days' => 90,
];
