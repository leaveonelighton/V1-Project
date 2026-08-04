# Sprint 1 — First Light Public Launch Review

**Review date:** August 3, 2026
**Authoritative commit:** `86988c55f6b44e3fac0c27f10a922d9e152fed36`
**Working branch:** `audit/sprint-1-first-light`
**Deployment:** None
**Commit/push/merge:** None

## Executive summary

The public First Light launch matches the authoritative GitHub revision. The homepage, Research index, flagship article, and Progress page were downloaded from production and compared byte for byte with commit `86988c5`; all four matched. The flagship article and its launch metadata are present publicly.

The site communicates a movement of hope and practical compassion rooted in the Gorman family story, serves people looking to understand or act on foster care and related community needs, and gives visitors clear next steps through the story, resources, research, books, and involvement pages.

The audit found two verified issues suitable for immediate correction:

1. Eight indexable public pages—including the flagship article—were missing from `sitemap.xml`.
2. The U.S. Department of Education resource on the Recommendations page returned HTTP 404; the agency's current replacement page returned HTTP 200.

Both issues were corrected locally. No architecture, navigation, branding, feature set, or approved page copy was changed.

## Scope and method

- Reviewed all 115 movement-site HTML pages in the repository. The separately hosted book-site directory was excluded from movement-site corrections.
- Parsed every page for title, description, canonical, Open Graph, Twitter Card, JSON-LD, H1–H3 hierarchy, links, images, controls, labels, accessible names, and new-tab safety.
- Compared every repository-local link target with the filesystem.
- Tested 71 unique external HTTP(S) destinations with direct requests, redirects enabled, a 25-second timeout, and a browser-like user agent.
- Requested public site pages directly and recorded response status, transfer size, time to first byte, total response time, and redirects.
- Inspected CSS for focus treatment, skip-link behavior, responsive breakpoints, and reduced-motion handling.
- Inspected asset sizes and image dimensions.

An instrumented graphical browser was not available in this workspace. Therefore keyboard traversal, rendered color sampling, browser screenshots, LCP, CLS, and INP were not fabricated or inferred. Those checks are listed under deferred verification.

## Findings

### SEO review

- All 115 pages have a non-empty page title and meta description.
- All 115 pages have exactly one canonical URL.
- Canonicals agree with the expected public route.
- All pages include Open Graph and Twitter Card metadata.
- The shared social image exists at 1200 × 630 pixels.
- Every page has exactly one H1; no H1–H3 level skips were found.
- JSON-LD found in the repository parsed without syntax errors.
- The flagship article contains `Article` schema, headline, publication date, author, publisher, main entity URL, and social metadata.
- Fifty-seven reserved Library pages intentionally use `noindex` and are correctly absent from the sitemap.
- Eight other indexable pages were missing from the sitemap and have now been added.
- Some older or specialized pages do not contain JSON-LD. This was not changed wholesale because schema is optional, the current metadata is accurate, and mass insertion would exceed a verified launch correction.

### First impression review

**What is this?**
Leave One Light On is a movement of hope and practical compassion inspired by the Gorman family's decision to welcome children at 5 Cedar Hill Road and by *The Light in the Window*.

**Who is it for?**
It is for visitors looking for hope, people learning about foster care and related needs, and people ready to take one practical act of compassion.

**What should I do next?**
The homepage offers three immediate routes: read the story, meet the Gormans, or explore the books; it then points visitors toward help, research, resources, and involvement.

**Clarity recommendations (maximum 10):**

1. No homepage copy change is recommended during this sprint. The first screen establishes the founding story and offers clear next actions.
2. During owner QA, ask one person unfamiliar with the project to identify the movement—not only the book—after viewing the first screen for five seconds. If they identify only a book, consider a later, owner-approved one-sentence clarification. Do not change the approved hero without that evidence.

### Link audit summary

- Repository-local link targets: no broken internal targets found.
- Unique external HTTP(S) destinations tested: 71.
- Confirmed broken link: one U.S. Department of Education URL returned HTTP 404.
- Confirmed correction: replaced it with the current official Department of Education foster-care resources URL, which returned HTTP 200, in both the rendered Recommendations page and its source JSON.
- Observed redirects included Amazon KDP locale routing, a legacy Child Welfare Information Gateway route, a Penguin Random House book route, and Travis Manion Foundation routing to The Mission Continues. These destinations ended at HTTP 200 and were not treated as broken.
- Several external sites returned HTTP 403 or HTTP 500 to automated requests, including AdoptUSKids, Amazon product pages, AmeriCorps, FosterClub, SAMHSA, Simon & Schuster, and Save the Children. These responses are consistent with bot protection or edge behavior and are not sufficient evidence of visitor-facing breakage; no URLs were changed on that basis.
- External response times in this audit environment were generally about 3–8 seconds. Because that latency affected many unrelated domains, it cannot reliably identify a specific slow partner destination. No destination was removed or replaced solely for speed.
- Repeated links in global navigation, mobile navigation, and footers are intentional interface duplication. No harmful duplicate destination was found.

### Accessibility summary

Verified static findings:

- Every page includes a skip link to `#main-content`.
- Every reviewed page has one H1 and a valid H1–H3 sequence.
- No missing image `alt` attributes or missing local image files were found.
- No unlabeled input, select, or textarea controls were found.
- No empty accessible names were found on links or buttons.
- New-tab links include `noopener`.
- CSS provides visible `:focus`/`:focus-visible` treatments.
- CSS includes mobile breakpoints and `prefers-reduced-motion` handling.
- Search/filter result messages use live-region semantics where present.

Deferred browser verification:

- Complete Tab/Shift+Tab traversal and focus order.
- Mobile menu keyboard behavior in a rendered browser.
- Computed color contrast for every rendered state, including hover/focus/dynamic content.
- Screen-reader smoke test for dynamic Light Board, Progress, Recommendations, and Trusted Organizations content.

No accessibility source edit was made without an observed defect.

### Performance summary

Direct production measurements (HTML only) returned HTTP 200:

| Page | HTML transfer | Observed TTFB | Observed total |
|---|---:|---:|---:|
| Homepage | 9,912 bytes | 4.11–4.84 s | 4.14–4.88 s |
| Research | 9,646 bytes | 3.49–7.34 s | 3.57–7.37 s |
| Flagship article | 17,971 bytes | 4.86 s | 4.92 s |
| Progress | 10,086 bytes | 4.82 s | 4.85 s |

These timings were collected from the restricted audit environment and should be confirmed from a normal browser/network before attributing the delay to Hostinger or changing code. HTML payloads are small.

Asset observations:

- Primary CSS: 23,534 bytes.
- Page JavaScript files: approximately 240–3,064 bytes each.
- Shared social image: 135,596 bytes, 1200 × 630.
- Book images: 89,836 bytes and 366,019 bytes.
- Core pages load a small number of local CSS/JS resources; Google Fonts is the principal third-party render dependency on pages that use it.
- Scripts on the launch-critical pages are deferred where present.
- No measurable source-level optimization was justified from the available data.

Core Web Vitals:

- **LCP:** Not available without a rendered, instrumented browser.
- **CLS:** Not available without a rendered, instrumented browser.
- **INP:** Not available without field data or an interactive browser session.

### Trust review

- Contact page publishes `info@leaveonelighton.org` and clearly warns visitors not to send private case or child-identifying information.
- Crisis/support routing is clearly separated from the movement's role.
- Copyright year is consistently 2026 on pages that contain the standard footer.
- The standard disclaimer accurately states that Leave One Light On is independent and is not a licensing, placement, government, crisis, legal, medical, or mental-health provider.
- Social metadata and the shared social image are present across all reviewed pages.
- No broken local images were found.
- Reserved Library entries explicitly state that no story or permission is implied and use `noindex`.
- Responsive CSS exists for the principal grids, navigation, articles, footer, progress display, Library, and interactive resource layouts.
- The Library index and 57 reserved entries intentionally use a minimal layout without the standard site footer. This is recorded as a consistency exception, not changed during this no-architecture sprint.
- Cross-browser rendering could not be directly verified without a graphical browser. The HTML/CSS uses broadly supported features; practical Chrome, Edge, Firefox, and Safari smoke tests remain pending.

## Completed fixes

1. Added the following indexable pages to `sitemap.xml`, with dates based on their latest repository commit:
   - `/contact.html`
   - `/gormans.html`
   - `/library-of-lights/`
   - `/living-pulse.html`
   - `/looking-for-hope.html`
   - `/research/why-fifty-seven-still-matters.html`
   - `/story.html`
   - `/welcome-shelf/`
2. Replaced the confirmed 404 Department of Education link with the current HTTP 200 official resource URL in:
   - `recommendations/index.html`
   - `data/resource-recommendations.json`

## Deferred improvements

1. Run Lighthouse or equivalent lab tests for homepage, Research, flagship article, and Progress from a normal browser/network.
2. Capture real LCP, CLS, and INP data through Search Console/CrUX after sufficient public traffic exists.
3. Perform keyboard-only and screen-reader smoke tests in a graphical browser.
4. Perform rendered contrast checks for normal, hover, focus, active, disabled, and dynamically generated states.
5. Perform Chrome, Edge, Firefox, and Safari desktop/mobile smoke tests.
6. Manually open bot-protected external destinations in a normal browser during owner QA.
7. Review whether the Library's intentionally minimal footer treatment should remain an approved exception in a later architecture/content decision.
8. Consider JSON-LD for older indexable pages only as a separately approved, page-type-specific SEO task.

## Priority recommendations

1. **Approve the two verified fixes** in this review: sitemap completeness and the Department of Education URL replacement.
2. **Run one normal-browser performance pass** before making performance changes; the small payloads do not support premature optimization.
3. **Complete owner browser QA** for keyboard traversal, contrast, mobile layout, and the bot-protected external links.
4. **Submit the corrected sitemap** through Google Search Console after a separately approved deployment.

## Validation after fixes

- All 115 movement-site pages still have title, description, canonical, Open Graph, Twitter metadata, one H1, and valid heading order.
- All JSON-LD payloads parse successfully.
- No broken repository-local link or image target was found.
- Every indexable movement-site canonical is now represented in `sitemap.xml`.
- The replacement Department of Education destination returns HTTP 200.
- JSON source files parse successfully.
- JavaScript syntax checks pass.
- `git diff --check` passes.
- No commit, push, merge, publish, or deployment was performed.

## Screenshots

No before/after screenshots are included because an instrumented graphical browser was unavailable. The applied corrections affect the sitemap and an external link destination; they do not change visual layout.

## Recommended commit message

`fix: complete launch sitemap and repair education resource link`

## Changed files

- `SPRINT-1-LAUNCH-REVIEW.md`
- `data/resource-recommendations.json`
- `recommendations/index.html`
- `sitemap.xml`
