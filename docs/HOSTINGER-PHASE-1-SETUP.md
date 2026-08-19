# Hostinger Phase 1 Setup — Private Website Replies

Status: **testing setup only — do not merge PR #21 to production yet**

This checklist prepares the Hostinger environment to test the private two-way website reply prototype on the development branch. Do not place database passwords, encryption keys, HMAC secrets, or admin password hashes in GitHub.

## 1. Confirm PHP support

In hPanel:

1. Websites → Dashboard for `leaveonelighton.org`.
2. Open **PHP Configuration**.
3. Confirm PHP 8.2 or newer is active.
4. Confirm PDO / `pdo_mysql` and OpenSSL are available in the PHP environment.

Hostinger documentation:
- https://www.hostinger.com/support/1575755-how-to-change-the-php-version-of-your-hostinger-hosting-plan/
- https://www.hostinger.com/support/which-databases-and-data-tools-are-supported-at-hostinger/
- https://www.hostinger.com/support/which-web-standards-and-connectivity-features-are-supported-at-hostinger/

## 2. Create the private MySQL database

In hPanel:

1. Websites → Dashboard → Databases → **Management / MySQL Databases**.
2. Create a new database and a dedicated database user for this message system.
3. Use a unique strong database password.
4. Record the database host, database name, username, and password in a secure password manager. Do not paste them into GitHub issues, PR comments, or chat screenshots.

Hostinger documentation:
- https://www.hostinger.com/support/1583542-how-to-create-a-new-mysql-database-in-hostinger/
- https://www.hostinger.com/support/1583552-how-to-find-your-mysql-database-details-in-hostinger/

## 3. Import the schema

1. Open phpMyAdmin for the new database.
2. Select the new database.
3. Import `docs/private-messages-schema.sql` from PR #21.
4. Confirm these tables exist:
   - `conversations`
   - `messages`
   - `audit_events`
   - `rate_events`

Do not use an existing WordPress or other application database for this prototype.

## 4. Generate the three application secrets

Generate these privately:

### Encryption key
A random 32-byte key, Base64 encoded.

Example PHP command on a trusted machine:

```bash
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

### Rate-limit HMAC secret
At least 32 random bytes represented as hex or Base64.

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

### Admin password hash
Choose a unique admin password and create a PHP password hash:

```bash
php -r "echo password_hash('REPLACE_WITH_YOUR_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
```

Avoid leaving the plaintext password in shell history. A local PHP script or other trusted password-hash tool is preferable if available.

## 5. Create the private configuration outside `public_html`

The application looks first for:

```text
../private-config/leave-one-light-on-messages.php
```

For the usual Hostinger layout this means a `private-config` directory alongside `public_html`, not inside it.

Copy the structure from `config/private-messages.example.php` and fill in the real values privately.

Required fields:

```php
return [
    'db_host' => '...',
    'db_name' => '...',
    'db_user' => '...',
    'db_pass' => '...',
    'crypto_key_b64' => '...',
    'rate_hmac_secret' => '...',
    'admin_user' => '...',
    'admin_password_hash' => '...',
    'inactive_close_days' => 180,
    'closed_delete_days' => 90,
];
```

Do not place the real file in the repository.

## 6. Protect the admin directory in hPanel

Use Hostinger **Password Protect Directories** to protect:

```text
/admin/messages/
```

Use credentials that are different from the application-level admin credentials if practical. This creates two layers before an administrator reaches the inbox.

Hostinger documentation:
- https://www.hostinger.com/support/1583470-how-to-password-protect-a-website-in-hostinger/

## 7. Testing deployment rule

Do **not** merge PR #21 into `main` for testing because Hostinger auto-deploys `main` to the public site.

Use a separate non-production testing location or Hostinger staging/subdomain approach that does not replace the production document root. The prototype branch must remain isolated until functional and visual QA are complete.

The test location needs the Phase 1 branch files plus access to the private configuration file and test database.

## 8. Functional test sequence

Run these in order and record pass/fail without copying private codes or personal test data into public GitHub comments.

1. Open `/contact.html`; verify four choices render correctly.
2. Open `/communicate/`; submit a private-reply message with no nickname, email, or phone.
3. Confirm a private code is returned once and the page says to save it.
4. Use `/communicate/check-response.html` with an intentionally wrong code; confirm no data is disclosed.
5. Use the correct code; confirm only that conversation appears.
6. Open protected `/admin/messages/`; confirm both protection layers work.
7. Reply from the admin conversation page.
8. Return with the visitor code; confirm the reply appears.
9. Add a visitor follow-up; confirm it appears in the admin thread.
10. Close the conversation in admin; confirm another visitor follow-up is rejected.
11. Test email/text/phone response choices with non-sensitive test contact details; confirm the administrator can decrypt and view the chosen contact value.
12. Test `No reply needed`; confirm no contact field is required.
13. Test honeypot and too-fast submission behavior.
14. Test repeated wrong-code requests until throttling activates.
15. Confirm dynamic response pages send no-store/noindex behavior and do not appear in browser history cache after logout/close where the browser respects those headers.
16. Confirm no PHP warnings, stack traces, database credentials, or server paths are shown to visitors.

## 9. Retention test before scheduling

Run `maintenance/private-message-retention.php` manually from the Hostinger cron/test environment only after making disposable test records.

Confirm:

- old inactive open conversations become closed;
- old closed conversations are deleted;
- associated message rows are removed by the foreign key cascade;
- old rate-limit events are purged;
- current active conversations remain untouched.

Only after this passes should a daily PHP cron job be added.

Hostinger cron schedules are UTC.

Hostinger documentation:
- https://www.hostinger.com/support/1583465-how-to-set-up-a-cron-job-at-hostinger/

## 10. Release gate

Before PR #21 can be considered for production:

- Hostinger functional test: PASS
- Security/secret-placement review: PASS
- Resource prototype render QA: PASS
- Authoritative wording reconciliation: PASS
- Owner visual/editorial approval: PASS

**Hostinger auto-deployment is enabled on `main`. Merging PR #21 would publish automatically and requires explicit owner authorization.**
