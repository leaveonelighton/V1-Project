# The Gorman Legacy & The Other 56 — owner review

Prepared November 6, 2026 (UTC verification November 7). Draft only.

## What was recovered and preserved

- Existing WordPress page 286, **The 57 Lives Changed**, `/the-57/`, is published and returned HTTP 200. The homepage’s “READ ABOUT THE FIFTY-SEVEN” link resolves correctly. No routing failure or visible stale redirect was found in that request. No cache purge is justified by the evidence.
- Public primary navigation includes The Gorman Legacy, but no direct The 57 / The Other 56 entry. The current homepage link sits below the book section; this is a discovery issue rather than a missing page.
- Existing published **I Am One of the Vanished Ones** (9048), **They Didn’t Have to Love Me. They Chose To.** (9085), **The Gorman Legacy**, and **About Ted** (260) were inspected and left unchanged, including photographs, wording, authorship and status.
- Page 286’s existing paragraphs, including **Making room for first-person stories**, are preserved in the proposed same-page update. The title, ID and URL remain unchanged. The only existing style refinement is responsive main-heading sizing.
- Existing SOMETIMES 57 and 56 + 1 artwork and production assets remain intact. Existing live PR #54 work is retained. No store or financial work is included.

## Proposed website changes — not published

- Book homepage 356: retain its layout, photographs, book links and seasonal storytelling. Replace the principal “Sometimes 57” line with **Fifty-seven lives. One open door.** Keep **A home can change a life.** Add a direct hero link to `/the-57/`; refine the existing lower link/section to **The Other 56. Their stories. Their choice.**
- Page 286: add **THE OTHER 56**, with **“I was the first. But I wasn’t the last.”** clearly attributed to Ted. Identify “first official foster child” as his firsthand account. Attribute the total of 57 to the owner-identified 2003 obituary; no new archival verification or claim of independent verification was made in this assignment.
- Preserve the historical closing **It started with room for one more. It didn’t end there.** Use **And it doesn’t have to end here.** for today’s forward invitation.
- Add links from page 286 to the four existing published resources, the existing book section and a practical LOLO action.
- Proposed navigation addition: **The Other 56** → `/the-57/`, after The Gorman Legacy in the same desktop/mobile navigation. This is specified in the review manifest, not saved to WordPress navigation.
- LOLO homepage and existing `gormans.html`: small copy/link revisions send visitors to the established book-site destination; no duplicate history page or homepage redesign. Existing anchor IDs remain usable.
- The WordPress original/proposed files are review source for the SAME page IDs, not new pages. Before applying, recheck the recorded modification times/hashes and reconcile any later owner edits. Do not blindly overwrite newer content.

## Recovered submission capability and prepared completion

The existing LOLO Light Board already has an encrypted private original, a pending moderation queue, protected admin review with CSRF, receipt-based status/withdrawal, rate limiting, honeypot and timing checks, consent controls, and retention tooling. Its live feed returned `accepting:false` and no items. WordPress page 286 has no form or shortcode; the active plugin inventory does not establish an existing Gorman intake mechanism.

The draft reuses that queue and its infrastructure. It adds a Gorman intake form with optional name/pseudonym and private email, anonymity or author-supplied attribution, optional explicit publication consent (unchecked means private review only), a story field, privacy/retention details and removal instructions. Public contact fields are excluded. Marketing reuse is disabled for this lane. Only human approval can release a consented story. Gorman accounts are excluded from the general Light Board feed; a moderated account has an individual LOLO story URL and links back to the existing book page. WordPress never auto-creates a story or page.

A separate `gorman_stories_enabled` switch defaults OFF. The form fails closed without both that switch and its schema. No live form is linked from the proposed page while intake is closed. A future approved CTA targets `https://leaveonelighton.org/light-board/gorman-story.php`.

## Verification and remaining gates

- Public homepage and `/the-57/`: HTTP 200; correct existing homepage link; primary navigation inspected.
- Current page screenshots captured and inspected at 390 px and 1280 px: text and the first-person-story section are visible without apparent clipping; mobile menu is collapsed in the capture (interactive menu behavior was not tested). Review previews are included for the proposed book homepage, existing page 286, and the proposed closed intake/form layout. Browser policy prevented opening local preview files in the cloud browser; no local-preview screenshot or browser viewport measurement is claimed.
- Local structural privacy checks, JavaScript syntax, internal links/assets and accessibility baseline pass. Two existing Light Board heading-level warnings remain outside this assignment.
- PHP integration tests use only an in-memory database and synthetic text/contact values. Local PHP execution is unavailable, but GitHub Light stories QA run 42319990681 passed PHP lint, maintenance lint, JavaScript syntax, synthetic submission/moderation lifecycle tests, privacy/page structure, and Gorman preservation/default-off checks. All six PR workflows passed at commit 975834efc63917c825ca86325bbf3a0cff431af1.
- Before public intake: install the additive schema in staging (or fresh schema, never both), verify encryption/config and protected admin access, test HTTP rate/spam handling, admin authentication/CSRF, private-only vs consented publication, chosen attribution, anonymous display, no contact leak, withdrawal after publication, feature-off behavior, mobile usability and daily retention. No real stories are required.
- Confirm hosting’s private-data backups and retention handling; cleanup removes records from the active database, not necessarily older backups. The exact privacy-policy addendum is prepared in privacy-policy-addendum.html; approve and append it to the existing policy before activation, after confirming backup handling.

## Owner decisions

1. Approve the wording, same-page refinements, navigation label and privacy/consent terms.
2. After passing staging checks, separately approve deployment of website refinements and separately approve opening submissions. These are distinct decisions.
3. Assign the owner/moderator who reviews accounts and handles removal requests. Do not locate or identify former foster children without their permission.

**Readiness: prepared for owner review; not published; public Gorman submissions not enabled.**
