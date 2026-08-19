# Phase 1 Private Reply + Resource System Prototype

Status: **development branch only — not production approved**

Branch: `phase-1-private-reply-resource-prototype`

## Purpose

This prototype proves two related V4.1 directions without changing production:

1. A privacy-conscious two-way website communication option that does not require a visitor to provide a personal or work email address.
2. A web-first resource system where printable PDFs are generated only for tools that benefit from offline, repeated, or write-in use.

## Private two-way communication

### Visitor flow

- Contact page offers Send a Message, Check My Response, Email, and Need Help.
- Private website reply is the default response method.
- Name/nickname is optional.
- Email or phone is required only when the visitor chooses email, text, or phone.
- No-reply submissions are supported.
- Newsletter or ongoing-update consent is not bundled into the message form.
- Every reply-enabled conversation receives a high-entropy private response code.
- The visitor can return with that code, read the thread, and add follow-up messages while the conversation remains open.

### Security and privacy design

- Private lookup codes contain 128 bits of random data.
- Only a SHA-256 digest of the normalized private code is stored in the database.
- Optional email/phone details are encrypted with AES-256-GCM using a key stored outside the repository.
- Database credentials, encryption keys, rate-limit secrets, and admin password hashes must never be committed.
- Public form rate limiting stores only an HMAC of the request IP and purges rate events frequently.
- Dynamic private-message responses send no-store/noindex plus nosniff, no-referrer, clickjacking, permissions, and same-origin content-policy protections.
- The public API exposes no conversation lists or sequential identifiers.
- The admin inbox requires application-level Basic Auth; Hostinger directory password protection should be added as a second layer before production.
- Admin write actions use session CSRF tokens.
- All message text is stripped of HTML and escaped on output.
- Attachments are intentionally excluded from Phase 1.
- Public communication pages and admin pages are marked noindex; admin responses also send no-store/private headers.

### Sensitive-information boundary

The public form tells visitors not to submit Social Security numbers, financial account information, passwords, medical records, private foster-care case information, or information that identifies a child. The system is not a crisis service and links to the existing Need Help page.

### Retention policy prototype

- Open conversation inactive for 180 days: close.
- Closed conversation retained for 90 additional days: delete.
- Manual deletion may occur sooner when appropriate.
- `maintenance/private-message-retention.php` implements the close/delete cycle and purges old rate-limit events.
- `maintenance/.htaccess` blocks web access to the maintenance directory, and the script also refuses non-CLI execution.
- The retention script is included but **not scheduled**. It must be tested first, then added as a Hostinger PHP cron job. Hostinger cron schedules use UTC.

## Hostinger setup required before functional testing

1. Confirm PHP 8.2+ and MySQL/MariaDB are enabled for the hosting plan.
2. Confirm the PHP environment supports PDO/pdo_mysql and OpenSSL.
3. Create a private MySQL database and user.
4. Run `docs/private-messages-schema.sql` in phpMyAdmin.
5. Create a 32-byte random encryption key and store it Base64-encoded.
6. Create a long random HMAC secret for rate limiting.
7. Generate an admin password hash using PHP `password_hash()`.
8. Create the real configuration outside `public_html` at:
   `../private-config/leave-one-light-on-messages.php`
9. Use `config/private-messages.example.php` only as a template.
10. Add Hostinger directory password protection to `/admin/messages/`.
11. Test HTTPS, PHP sessions, database connectivity, create/check/follow-up/reply/close paths, wrong-code throttling, contact encryption/decryption, and the retention job before any release.
12. Only after retention testing, schedule `maintenance/private-message-retention.php` as a Hostinger PHP cron job using a conservative daily schedule.

## Resource-system prototype

### Rule

Accessible HTML is the canonical digital resource. A print/PDF edition is created only when the artifact benefits from printing, writing, carrying, repeated offline use, or group facilitation.

### Prototype resources

- `welcome-shelf/one-meaningful-step.html` — one-page / short worksheet pattern.
- `welcome-shelf/story-preservation-workbook.html` — multi-page workbook pattern.
- `welcome-shelf/one-light-at-work.html` — complex facilitation pattern with wrap-safe invitation cards.
- `prototype/pdf-qa.html` — destructive print-layout QA harness.

The Story Preservation Workbook and One Light at Work prototypes intentionally do **not** claim final authoritative wording. Final copy must be reconciled against the existing source materials before release.

### Shared resource styles

- `css/resource-system.css` — screen/readable resource components.
- `css/resource-print.css` — Letter-size print/PDF behavior.

Print rules intentionally avoid global `word-break: break-all`, horizontal-scroll assumptions, fixed-position headers/footers, and global `page-break-inside: avoid` on ordinary paragraphs.

### QA gate

Before a resource is released:

1. Reconcile content against its authoritative source.
2. Validate headings, links, labels, and plain-language structure.
3. Print/render the resource at Letter size.
4. Run the destructive QA harness: unbroken strings, long URLs, long headings, large components, 50-row content, card/page-boundary cases.
5. Inspect every rendered page visually for clipping, overflow, bad breaks, unreadably small type, and collisions.
6. Check extracted reading order/text behavior separately from visual appearance.
7. Obtain owner approval.
8. Only then prepare a production pull request.

## Separate book-site package noticed during QA

The uploaded `LITW-V4-Site-v4.1.3-CORE-PAGES` package is for the **book site / WordPress blog layer**, not this movement-site repository. It is therefore not being mixed into this Phase 1 branch. Its README contains an older Texas formation-status sentence and should be reviewed separately before the book-site package is next published.

## Release gate

This prototype branch is **not authorized for merge or deployment**. Hostinger auto-deployment is tied to `main`; merging a future approved PR to `main` will publish automatically and therefore requires explicit owner authorization at that time.
