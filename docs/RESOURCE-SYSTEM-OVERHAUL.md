# Resource System Overhaul

Status: **release-candidate branch only - not approved for production**

Branch: `resource-system-overhaul-v1`

## Purpose

Rebuild the Welcome Shelf as a web-first practical resource system rather than a file cabinet of PDFs.

The rule is simple:

**Accessible HTML is the canonical digital resource. A PDF/print edition exists only when printing, writing, carrying, repeated offline use, or group facilitation adds real value.**

For this release candidate, browser print from the approved HTML is the canonical print edition for rebuilt movement resources. A separate static PDF is not required merely to duplicate the same content.

## Visitor organization

The Welcome Shelf is organized around what a visitor wants to do:

- Reflect
- Learn & Prepare
- Act Together
- Take One Step

Book-reader materials remain available, but are treated as a separate doorway toward the Books area rather than as the center of the movement resource library.

## Accessibility, dignity, and visual rhythm

Accessibility is not limited to font size or technical compliance. The resource system should feel orderly enough that a visitor can understand where to look next without unnecessary visual effort.

Internal design rules:

- Prefer symmetry, aligned edges, equal-height paired elements, and predictable spacing where they improve scanning.
- Treat visual balance as functional: uneven controls, drifting baselines, crowded boxes, and arbitrary differences in button size can create avoidable reading friction.
- Keep layouts calm, plain, high-contrast, and easy to re-enter after the reader looks away.
- Do not confuse a learning difference with intelligence, motivation, character, professionalism, or potential.
- Design for the person who may already have spent years being misunderstood. The resource should invite them in rather than make them work to decode the page.

## The present-action principle

The movement can acknowledge history without trapping a person in blame, resentment, or fear. Resources should help a visitor return to the present question: **What can I understand, choose, or do now?**

This is an internal content principle, not an additional public slogan. It supports the existing practical-compassion direction: learn from what happened, protect what matters, then make the next useful choice in the present.

## Current release-candidate resources

- `welcome-shelf/story-preservation-workbook.html`
- `welcome-shelf/foster-care-start-here.html`
- `welcome-shelf/reading-learning-questions.html`
- `welcome-shelf/resource-navigation-notes.html`
- `welcome-shelf/one-light-at-work.html`
- `welcome-shelf/community-light-starter-kit.html`
- `welcome-shelf/one-meaningful-step.html`
- `books/the-light-in-the-window-reader-materials.html`

Internal QA support remains at:

- `prototype/pdf-qa.html`
- `css/resource-system.css`
- `css/resource-print.css`

## PDF disposition

### Book-specific PDFs - remain active

- `welcome-shelf/book-club-welcome-kit.pdf`
- `welcome-shelf/discussion-guide.pdf`

These files are explicitly tied to *The Light in the Window* and are linked only from the Books-area reader-materials page.

### Legacy general-resource PDFs - source/archive only

- `welcome-shelf/foster-care-awareness-primer.pdf`
- `welcome-shelf/story-preservation-workbook.pdf`
- `welcome-shelf/workplace-conversation-guide.pdf`
- `welcome-shelf/community-light-starter-kit.pdf`

These files remain in the repository as authoritative/history sources where applicable, but the general Welcome Shelf does not link to them. Their rebuilt HTML pages are the canonical public resources and provide the approved browser-print editions.

Do not relink a legacy general-resource PDF merely because it exists in the repository. Any future static replacement PDF must be generated from the approved HTML/CSS source and pass the same print QA gate before publication.

## One Light at Work source rule

The legacy `workplace-conversation-guide.pdf` remains the source record for the invitation examples and boundaries that were reconciled into the approved HTML rebuild.

Do not invent missing wording.

The six invitation labels carried forward are:

- Give time
- Give skill
- Give practical support
- Give life
- Give opportunity
- Give attention

The approved layout replaces the rigid three-column invitation table with independent wrap-safe cards so long example/boundary text can grow vertically without collisions.

## Evidence-guide rule

Changing explanations, statistics, eligibility details, contact finders, and source links remain web-first.

- Foster care companion points back to the living Foster Care 101 evidence guide.
- Reading & Learning companion points back to the living Dyslexia & Reading Difficulties evidence guide.
- Printable companions help a visitor ask questions, verify information, take notes, and choose a next step; they do not become frozen directories or substitute for current official information.

## Production gate

For each printable resource:

1. Confirm purpose and audience.
2. Reconcile content against authoritative source material and credible evidence where applicable.
3. Build/review accessible HTML.
4. Apply print/PDF design from the same source.
5. Run destructive layout QA.
6. Render at 200 dpi and visually inspect every page where a static PDF is generated.
7. Check reading order, links, labels, and extracted text behavior.
8. Obtain owner approval.
9. Only then prepare a production release decision.

## Print rules

- Letter size unless the resource has a clear reason to use another format.
- Large plain sans-serif type and strong contrast.
- No global `word-break: break-all`.
- No fixed table row heights.
- No horizontal-scroll solution for PDF tables.
- No global `page-break-inside: avoid` on all paragraphs or list items.
- No forced new page for every section.
- Critical text belongs in HTML, not CSS pseudo-elements.
- Avoid fixed headers/footers that can collide with content.
- Prefer a usable form or card layout over a dense spreadsheet-style grid.

## QA and release guards

`prototype/pdf-qa.html` includes destructive cases for:

- unbroken strings
- long URLs
- long headings near page boundaries
- wide tables
- 50-row pagination
- components larger than one page
- atomic invitation cards

Automated guards include:

- Resource system QA for source reconciliation and print anti-patterns.
- Book reader source inspection for book-specific separation.
- Resource release audit for visitor-facing review language, staging `noindex`, retired PDF links, and key destination existence.

## Final staging review

Before any production decision, sync the latest branch to `phase1.leaveonelighton.org` and complete one final visitor-path review of:

- Welcome Shelf at normal zoom and 50% zoom.
- Each resource page and its print action.
- Foster Care 101 and Dyslexia & Reading Difficulties evidence-guide links.
- Book reader materials and both book-specific PDFs.
- Back-navigation and Choose One Next Step links.

No production toggle is implied by a successful staging review.

## Release switch

All release-candidate pages remain `noindex,nofollow` while they are on staging. Removing those page-level `noindex` directives is a deliberate release action and must not happen early.

## Release warning

Hostinger auto-deployment is connected to `main`. Merging a future resource release PR to `main` will publish automatically and requires explicit owner authorization.
