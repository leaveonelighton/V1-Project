# Private Communications Release

Status: **release candidate branch only — not production approved**

This branch isolates the private two-way communications feature from the broader Phase 1 resource/PDF prototype.

## Included

- Contact-page communication choices.
- Private website message form with optional nickname.
- Private response-code lookup and visitor follow-up messages.
- Optional email, text, phone, and no-reply choices.
- AES-256-GCM encryption for optional contact details.
- Hashed private lookup codes.
- HMAC-based transient rate limiting.
- Protected admin inbox with reply and close actions.
- CSRF protection on admin write actions.
- CLI-only maintenance tools for self-test, exact-reference cleanup, and retention.
- MySQL/MariaDB schema and example private configuration.
- CI lint, secret guard, and pure-function smoke tests.

## Staging verification completed 2026-08-19

The feature was exercised on `phase1.leaveonelighton.org` against Hostinger PHP 8.3 and MySQL.

Verified:

- PHP / PDO / pdo_mysql / OpenSSL environment.
- Database connection and schema.
- Visitor create -> private code -> lookup round trip.
- Admin inbox -> private reply -> visitor retrieval round trip.
- Visitor follow-up path.
- Email, text, phone, private website reply, and no-reply choices.
- Optional contact details are stored encrypted and decrypt correctly server-side.
- Private-code hashing and normalization.
- Admin Basic Auth rejects unauthenticated requests with HTTP 401.
- Dynamic private-message responses use no-store/noindex security headers.
- Staging subdomain is noindex and disallowed in robots.txt.
- Retention close/delete behavior using synthetic aged records.
- Test records were cleaned after verification.

## Production prerequisites

Before merge/release:

1. Keep the real configuration outside `public_html` at `../private-config/leave-one-light-on-messages.php`.
2. Confirm production uses the existing private-message MySQL database and user; never commit credentials or keys.
3. Add Hostinger directory-level password protection to `/admin/messages/` as a second layer in addition to application Basic Auth.
4. After deployment, run `maintenance/private-message-self-test.php` from SSH/CLI.
5. Verify the production Contact, Send a Message, Check My Response, admin inbox, reply, follow-up, and close paths over HTTPS.
6. Schedule `maintenance/private-message-retention.php` as a once-daily Hostinger PHP cron job only after the production self-test passes.
7. Confirm the maintenance directory remains blocked from web access.
8. Do not reuse staging test credentials or any password that was exposed during testing.

## Retention defaults

- Open conversation inactive for 180 days: close.
- Closed conversation retained for 90 additional days: delete.
- Old rate-limit events are purged separately by the maintenance job.

## Release gate

Hostinger auto-deployment is enabled on `main`. **Merging this release PR to `main` will publish automatically.** Merge requires explicit owner authorization at release time.
