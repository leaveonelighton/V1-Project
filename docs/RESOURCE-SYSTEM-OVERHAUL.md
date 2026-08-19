# Resource System Overhaul

Status: **development branch only - not approved for production**

Branch: `resource-system-overhaul-v1`

## Purpose

Rebuild the Welcome Shelf as a web-first practical resource system rather than a file cabinet of PDFs.

The rule is simple:

**Accessible HTML is the canonical digital resource. A PDF/print edition exists only when printing, writing, carrying, repeated offline use, or group facilitation adds real value.**

## Visitor organization

The prototype is organized around what a visitor wants to do:

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

## Current prototype resources

- `welcome-shelf/one-meaningful-step.html`
- `welcome-shelf/story-preservation-workbook.html`
- `welcome-shelf/one-light-at-work.html`
- `prototype/pdf-qa.html`
- `css/resource-system.css`
- `css/resource-print.css`

Existing PDFs remain untouched on this branch.

## Existing PDF disposition

1. Book Club Welcome Kit - move toward the book-reader/story area.
2. Discussion Guide - review whether it is book-specific or should become a standalone movement conversation guide.
3. Foster Care Awareness Primer - rebuild as web-first Foster Care 101 plus a concise printable companion if useful.
4. Story Preservation Workbook - keep and rebuild as a strong workbook/print candidate.
5. One Light at Work - keep and rebuild as a practical workplace conversation and action guide.
6. Community Light Starter Kit - keep and rebuild.

## New candidates

- One Meaningful Step Planner
- Reading & Learning - Questions to Ask
- Resource Navigation Notes

Research before adding:

- Supportive Adult / Mentoring Boundaries
- Opportunity / Education & Work Pathway

## One Light at Work source rule

The current `workplace-conversation-guide.pdf` is the wording authority for existing invitation examples and boundaries until the owner approves revisions.

Do not invent missing wording.

The six invitation labels currently carried forward are:

- Give time
- Give skill
- Give practical support
- Give life
- Give opportunity
- Give attention

The new layout replaces the rigid three-column invitation table with independent wrap-safe cards so long example/boundary text can grow vertically without collisions.

## Production gate

For each printable resource:

1. Confirm purpose and audience.
2. Reconcile content against authoritative source material and credible evidence where applicable.
3. Build/review accessible HTML.
4. Apply print/PDF design from the same source.
5. Run destructive layout QA.
6. Render at 200 dpi and visually inspect every page.
7. Check reading order, links, labels, and extracted text behavior.
8. Obtain owner approval.
9. Only then prepare a production release PR.

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

## QA harness

`prototype/pdf-qa.html` includes destructive cases for:

- unbroken strings
- long URLs
- long headings near page boundaries
- wide tables
- 50-row pagination
- components larger than one page
- atomic invitation cards

## Release warning

Hostinger auto-deployment is connected to `main`. Merging a future resource release PR to `main` will publish automatically and requires explicit owner authorization.