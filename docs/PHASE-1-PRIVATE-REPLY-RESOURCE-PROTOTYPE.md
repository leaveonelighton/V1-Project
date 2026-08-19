# Phase 1 Private Reply + Resource System Prototype

Status: **development branch only — not production approved**

Branch: `phase-1-private-reply-resource-prototype`

## Purpose

This prototype proves two related V4.1 directions without changing production:

1. A privacy-conscious two-way website communication option that does not require a visitor to provide a personal or work email address.
2. A web-first resource system where printable PDFs are generated only for tools that benefit from offline, repeated, or write-in use.

## Private two-way communication

### Functional validation — staging passed 2026-08-19

The Phase 1 staging site at `phase1.leaveonelighton.org` has completed a real Hostinger/MySQL round-trip test:

- PHP 8.3.30 confirmed.
- PDO `mysql` driver confirmed.
- OpenSSL confirmed.
- Dedicated MySQL schema imported successfully.
- Private configuration loaded from outside `public_html`.
- Database authentication and connection confirmed.
- Visitor private-message creation confirmed.
- Private response-code lookup confirmed.
- Protected admin inbox confirmed.
- Admin reply confirmed.
- Visitor retrieval of the admin reply confirmed.
- Visitor follow-up path confirmed as available in the returned conversation.

This proves the core two-way private website communication loop on staging. It does **not** authorize production release.

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
- The admin inbox requires application-level Basic Auth; Hostinger directory password protection remains recommended as a second layer before production.
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
- `maintenance/private-message-cleanup.php` is a CLI-only exact-reference cleanup tool for test or intentionally removed conversations.
- `maintenance/private-message-self-test.php` validates database connectivity, contact encryption/decryption, and private-code hashing without creating message records.
- `maintenance/.htaccess` blocks web access to the maintenance directory, and the maintenance scripts also refuse non-CLI execution.
- The retention script is included but **not scheduled**. It must be tested first, then added as a Hostinger PHP cron job. Hostinger cron schedules use UTC.

## Hostinger staging workflow

Staging is hosted at `phase1.leaveonelighton.org` under the existing website account rather than using another website slot.

`scripts/phase1-update.sh` is the staging update command. It:

- fast-forwards the Phase 1 branch,
- syncs it only to `public_html/phase1`,
- excludes local secret configuration,
- applies an idempotent staging `noindex` guard,
- writes a staging-only `robots.txt` with `Disallow: /`,
- lints all Phase 1 PHP,
- runs the private-message cryptography/database self-test,
- checks Contact and Message pages for HTTP 200,
- checks the unauthenticated admin endpoint for HTTP 401,
- verifies the staging `X-Robots-Tag` header.

The script does not deploy or modify production `main`.

## Remaining private-message release checks

Before production release:

1. Test email response choice with an encrypted contact value and confirm correct admin decryption.
2. Test text response choice with an encrypted contact value and confirm correct admin decryption.
3. Test phone response choice with an encrypted contact value and confirm correct admin decryption.
4. Test no-reply submission behavior.
5. Test wrong-code throttling and public rate limiting.
6. Test close-conversation behavior and confirm visitor sees the closed state.
7. Run the retention script in a controlled staging test before scheduling it.
8. Add Hostinger directory password protection to `/admin/messages/` as a second layer if supported cleanly by the staging/production layout.
9. Delete exposed or obsolete staging test conversations.
10. Re-run `scripts/phase1-update.sh` and complete a final browser QA pass.

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
