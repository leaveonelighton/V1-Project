# Received Light. Shared Light. — review implementation

Status: **Owner approved publication October 23, 2026. Publish the redesigned presentation with intake disabled until the server activation gates pass.**

Prepared October 23, 2026 from main tree `8ed4ffb731a0073e423aa93b120438fd507aa1d1`.

## Authorized direction

Ted requested a redesign of One Light Each Month and The Light Board, anonymous stories, human approval before publication, and an invitation to shift attention toward others. He accepted the research recommendation and interactive concept, then explicitly authorized the review commit on October 23, 2026. This implementation prepares that direction for concrete review. The production public-submission decision remains gated on the checks below and explicit release approval.

## Public experience

- Existing global navigation and established page URLs stay in place.
- Monthly page: holiday invitation, clearly fictional Leftovers link, a practical step, and direct read/share actions. Broken `info.org` signup link becomes a request to the already-public `contact@leaveonelighton.org` address. No email provider, mailing list, paid service, or automatic subscription is added.
- Board: direct Share a Light, clearly attributed founder reflection, and reviewed community stories. No empty-scoreboard counters or map, fabricated community entries, comments, profiles, or likes.
- Share: received/witnessed/offered perspective; story; optional reflection; required anonymous website permission; separate optional LOLO newsletter/social reuse permission.
- Private receipt: status, reviewer clarification note, and withdrawal. Codes are sent by POST, never placed in a URL or browser storage. Losing a code means anonymous recovery is unavailable.
- Approved stories: individual public URLs, sanitized metadata, system share or copy-link with a visible-link fallback.

## Data and review

The additive MySQL schema uses the existing private-message database. The queue is separate from private correspondence. The existing admin login and CSRF/session controls are reused; there is no new authentication provider.

Every submission is pending, including requests that try to send an approved state. Originals are encrypted with the existing server-side key. Receipt codes are hashed. Public views select only approved, website-consented rows and return an explicit public-field list. Possible contact details, links, and markup are flagged on intake and block approval in public wording. Human reviewers still check names, context, suitability, authenticity concerns, and sensitive details that simple patterns cannot recognize. Review is not independent factual verification.

Only a human approval action publishes. Passage of time never publishes. Concurrent review/withdrawal conflicts use a revision check in a transaction. Reviewer actions are recorded without copying story text into the event log. Withdrawing clears public wording; a withdrawn entry cannot be republished. Unpublishing retains reviewed wording privately for another review, including after raw-original retention has run.

Secondary reuse permission is displayed to the moderator but is not an automatic publishing integration. Staff must check it before featuring a story outside its public page. A visitor can share the URL of an already-public story; copies outside LOLO cannot be recalled.

## Retention proposed for release

CLI task defaults to dry run. With `--apply`, delete pending/clarification records inactive for 90 days, delete declined/withdrawn records after 30 days, erase private originals and notes for approved records after 30 days, and purge transient rate events older than two days. Public approved wording, consent, receipt hashes, and minimal audit records remain while published. Child audit rows cascade when a story is removed. Confirm hosting access-log retention separately. Do not enable until the daily maintenance task is configured and tested; no task has been scheduled in this review.

## Default-off release switch

`light_board_enabled` in the existing private config defaults to false. It can also be enabled with `LOLO_LIGHT_BOARD_ENABLED=1`. When off, submission returns 503, feed returns no community entries, and individual public story routes return 404. Status/withdrawal remain usable if the database exists. This is a whole-feature pause, not a separate intake-only pause.

## Checks and limitations

Local checks: browser-script syntax, form labels/IDs/structure, explicit permission fields, no private identifiers in the public projection, no unsafe HTML insertion or receipt browser storage, direct participation routes, default-off switch, and whitespace/diff checks.

`tests/light-story-flow.php` is an in-memory SQLite integration suite using a translated copy of the production schema. It covers pending-state enforcement, consent, encrypted originals, human review, clarification, public-field isolation, unpublishing, withdrawal, stale revisions, perspective filtering, and pagination. A dedicated workflow runs PHP lint and the integration suite after the branch is authorized for GitHub.

**PHP is not installed in this workspace, so PHP lint and executable lifecycle tests have not run locally.** MySQL behavior, HTTP status/withdrawal, existing admin credentials/directory protection, CSRF responses, and mobile/desktop browser rendering still need staging verification. Passing static checks is not proof of a working production queue.

## Release sequence

1. Owner authorized the review commit. Preserve the separate review branch; do not merge main, which auto-deploys.
2. Run the dedicated CI checks. Resolve every failure.
3. On a nonpublic staging environment, import the additive schema into a staging database; use staging-only keys and credentials. Never copy production submissions into tests.
4. Protect `/admin/light-board/` at the hosting directory level in addition to the existing application login; verify signed-out GET/POST rejection and CSRF failure.
5. Exercise synthetic submit → pending/invisible → approve/visible → unpublish/invisible → approve → author withdrawal/invisible. Verify receipt never appears in public feeds, HTML, share URLs, logs generated by the application, or metadata. Do not submit test stories to production.
6. Verify unknown/withdrawn public URLs return 404; outage returns a meaningful unavailable state, not invented empty participation. Test phone widths, keyboard flow, no-JavaScript submission/status/withdrawal, share/copy fallback, and real social previews for public synthetic staging content only if owner authorizes public staging.
7. Test daily retention in dry-run then on synthetic aged data; configure it before enabling.
8. Reconcile legacy Living Pulse/static JSON views with the new queue before production. They are not connected by this scoped review and must not be presented as current counts of new stories. No location collection or map is added.
9. Obtain explicit release approval. Deploy with feature off, verify pages/database/protection/retention, then enable the approved workflow and confirm with owner-approved testing.

No production configuration, financial settings, social accounts, mailing lists, or existing private-message records were changed.

## Exact changed-file list

- `.github/workflows/light-stories-qa.yml`
- `admin/light-board/index.php`
- `api/light-board/_service.php`
- `api/light-board/feed.php`
- `api/light-board/status.php`
- `api/light-board/submit.php`
- `config/private-messages.example.php`
- `css/light-stories.css`
- `docs/SHARED-LIGHT-REVIEW.md`
- `docs/light-board-schema.sql`
- `js/light-stories.js`
- `light-board/archive.html`
- `light-board/index.html`
- `light-board/methodology.html`
- `light-board/my-story.html`
- `light-board/share.html`
- `light-board/share-basic.php`
- `light-board/story.php`
- `maintenance/light-story-retention.php`
- `one-light-each-month.html`
- `privacy.html`
- `tests/light-story-flow.php`
- `tests/light-story-structure.py`

## Local verification recorded

- PASS: `python3 tests/light-story-structure.py` (six pages).
- PASS: `node --check js/light-stories.js`.
- PASS: `git diff --check`.
- PASS: translated additive migration parses in SQLite. This does not replace MySQL staging tests.
- NOT RUN: PHP lint and executable PHP lifecycle suite (runtime unavailable).
- NOT RUN: staging HTTP/auth/CSRF/retention/share-preview and responsive browser checks.

## Publication decision — October 23, 2026

Owner explicitly approved publication after the review commit. All six GitHub checks passed on `f2f05cf1586716f3c6969c0049feb2dc3f078722`, including executable PHP lifecycle tests. The new queue tables, directory protection, retention task, and staging verification have not been configured on Hostinger. The redesigned presentation may publish now with the whole-feature switch off. Share a Light must display an in-preparation message and keep its form hidden until server readiness is confirmed. A no-JavaScript fallback uses the same readiness check. Anonymous intake must not be described as operational before activation. Remaining server gates and legacy Living Pulse reconciliation still apply before enabling.
