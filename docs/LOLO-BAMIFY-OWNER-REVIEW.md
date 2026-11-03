# LOLO movement launch preparation — owner review

Prepared November 2, 2026 (America/Chicago). **Draft only. No merge, website publication, new financial collection, recurring enrollment, product publication or checkout activation authorized.**

## One coherent draft

[Existing draft PR #54](https://github.com/leaveonelighton/V1-Project/pull/54), branch `prep/lolo-merch-2026-11-01`, extends the prior six-product Coming Soon work. The live homepage and current main matched before this work. Main was `b8b712af72f8084f6214699acbb6a99c4f0b5cdf`; the prior PR head was `9a023eea9baec16d82fe775aaa902a59c699d4bd`.

Preserved: published story pages, research library, resource and participation destinations, organizational disclosure, existing homepage content, monthly invitation, anonymous submission/consent/withdrawal/moderation system, admin protection code and financial settings. Older homepage material remains available in a native expandable section. No unrelated sites, business systems, hosting, plugins or subscriptions changed.

## Prepared website improvements

- Immediate “Someone showed up for you” hero, genuine existing illuminated-window artwork, readable sans-serif headings and four prominent action routes.
- Support the Mission leads to the existing support URL; Shop with Purpose leads to the six-card homepage gallery; Share Your Light leads to the existing Light Board; Take Action leads to new time-based choices on the existing pathway page.
- Support page explains mission, possible uses, one-time support, monthly support in preparation, proposed $10/$25/$50/$100/custom amounts, tax limitations, acknowledgment language and accountability commitments. Proposed amounts are not payment controls.
- Action chooser offers five-minute, one-hour, ongoing and financial choices. Existing reviewed organizations and resources remain the deeper routes. Outside opportunities must still be checked with their providers before participation.
- Light Board and anonymous sharing are more discoverable. No queue logic, consent fields, data handling, pause switch or automatic publishing behavior changed.
- Homepage proof of life uses the dated August 14 incorporation milestone, an actual founder reflection and the existing monthly invitation. No new beneficiary, donation or participation totals are claimed. Founder reflections are not presented as verified community outcomes.
- Short founding bridge explains Peg and Jerry Gorman’s welcome and links to the full story, with the movement’s practical invitation clearly distinct from book sales.

## Fundraising status and exact stop

The live support page already contains an existing Stripe buy button. Its script, button ID and publishable key are byte-identical in this draft. The live widget rendered an editable amount and a Buy button during inspection; it was not clicked. Nothing was disabled or reconfigured. This inspection does **not** verify that it settles to the right legal entity, has an eligible bank, sends correct receipts, or is authorized for a new campaign.

The connected Stripe tool exposes **Ted Johnson Works only**. No TJW financial data was inspected beyond the account listing. The LOLO Stripe account cannot be independently verified through that connection. No new Stripe object, payment link, recurring price or checkout was created; no real or test payment was made.

Monthly enrollment stays unavailable. The proposed new amounts are preview copy, not settings applied to the existing checkout. Final wiring uses the existing verified LOLO provider account, independent of which bank is approved. No website rebuild is needed if the bank changes.

Acknowledgment draft for use after a verified successful contribution:

> Thank you for supporting Leave One Light On. We received your contribution of [amount] on [date], reference [provider reference]. Leave One Light On is a Texas nonprofit corporation. Federal 501(c)(3) recognition has not been obtained, and this acknowledgment does not represent the contribution as tax deductible. Questions: use the organization’s contact page.

Do not send an acknowledgment from a browser click or success URL alone. Confirm settled/provider-confirmed payment details first. For monthly support, show amount, monthly frequency, start timing and cancellation instructions before enrollment. Verify the actual provider receipt and support contact before activation. Merchandise order confirmations must identify the purchase rather than describe the entire price as a donation.

## Six-product readiness

All six existing private templates were present in the signed-in Printful account. Store **18946431**, Leave One Light On, was shown as **Inactive**; its **Enable checkout** switch was **false**. No templates were recreated and no product or price was published or changed.

| Product | Provisional retail | Preparation |
| --- | ---: | --- |
| Classic unisex T-shirt | $28 | Existing private template and supplier mockup reused |
| Oversized T-shirt | $38 | Existing private template and supplier mockup reused |
| Forest hoodie | $48 | Existing private template and supplier mockup reused |
| 15 oz ceramic mug | $22 | Existing private template and supplier mockup reused |
| 20 oz insulated tumbler | $40 | Existing private template and supplier mockup reused |
| One Light at a Time — 2027 calendar | $32 | Existing private template 109696015 and supplier mockup reused |

The PR’s prior review records the calendar’s cover and 12 date grids as checked; that full prior audit was not repeated. Current supplier preview imagery is copied from those same saved templates, 400×400 pixels, displayed at 200×200 pixels. The existing book-site window image is 1024×1024, optimized to 960×960 WebP for the movement draft. No new stock imagery was introduced. Cap and jewelry remain on hold.

Keep private templates and Coming Soon website messaging until owner approval and provider readiness. The store settings explain that disabling checkout leaves a public store unable to accept orders; that explanation is not an end-to-end test of every product route. No early product publication was necessary.

## Book-site draft 9091

[Authenticated preview](https://thelightinthewindowbook.com/?page_id=9091&preview=true)

The WordPress connector returned `rest_invalid_signature`; WordPress.com also displayed a Jetpack domain/DNS connection warning. No connection, DNS or plugin settings were changed. The signed-in site editor and authenticated preview **did work**.

The existing year-round hero, window image, articles, book links, holiday story features and six-type unavailable merchandise message were preserved. Only two links were added beside the existing movement link: Take One Meaningful Step and Support the Mission. Save completed, status remained Draft, and the saved links appeared after a fresh preview load. No live book-site page or payment infrastructure changed.

## Operational blocker: anonymous story intake

The live public feed returned `ok:true`, `accepting:false`, an empty items array and no cursor. This is an intentional whole-feature pause, not evidence that nobody has ever acted compassionately. The homepage says submissions are in preparation and links to the current availability explanation.

The existing `docs/SHARED-LIGHT-REVIEW.md` records that production queue tables, hosting directory protection, retention scheduling and staging verification have not been completed. Banking approval alone does not clear this blocker. Before enabling intake, verify the existing MySQL migration, staging-only submit → pending → human approval → unpublish → withdrawal flow, anonymous receipts, access/CSRF protection, consent boundaries and retention. Do not send synthetic stories to production. No additional application is proposed.

## Five operating metrics

No new analytics service, cookie banner, paid product or tracking script was installed. Repository inspection found no established analytics tag; host analytics availability/retention still needs confirmation. Begin with weekly aggregate counts and do not call page requests unique people or impact.

| Metric | Source and meaning |
| --- | --- |
| Action-path interest | Existing host analytics/access-log aggregate for pathway and reviewed-organization pages; exclude known bots where supported |
| Support-page interest | Aggregate requests to `/keep-the-light-on.html`; never treat as donations |
| Merchandise interest | A future consent-appropriate aggregate `merchandise_interest` event on the existing gallery anchor, only if an existing analytics system supports it; fragments are not visible in server logs. No event collector was activated in this draft |
| Story participation | Weekly pending/approved/withdrawn counts from the existing protected queue after intake opens; no story text, receipts or identities in analytics |
| Verified financial support | Provider-confirmed successful contributions, refunds and net receipts reconciled against LOLO records after activation; recurring support reported separately; never infer completion from page views |

Use only aggregate reports for publicity. Confirm the host’s raw-log retention and access controls; do not export IPs, names, story text, private receipts, card details or banking records to analytics. Set a minimal retention policy with the owner before enabling any event collection.

## Prepared communication sequence — do not post

1. Owner approves the website presentation. After it is published and checked, introduce the four action pathways on LOLO Facebook and the established movement channel. Link to the main website. Describe merchandise and monthly support as in preparation while they remain unavailable.
2. After financial approval, verification and separate activation authorization, verify exact public checkout availability. Then prepare the financial-support announcement with accurate options and tax-status wording.
3. After merchandise is public and checkout is verified, share the real collection, final prices and shipping information. Do not promise delivery dates without provider confirmation.
4. Invite story submissions only after the separate moderation/server gates pass. Until then, invite reading and everyday action. Feature community excerpts only where separate reuse consent exists.

Website-introduction draft: “Someone showed up for you. Be that person for someone else. Leave One Light On helps turn everyday compassion into one meaningful step. Explore ways to act, learn about supporting the mission, preview the coming collection, and discover stories of kindness. Start at LeaveOneLightOn.org.”

Support-announcement draft, held until verification and activation: “Financial support helps keep LOLO’s stories, educational resources and practical invitations available for the next person. Explore the available contribution options and our accountability information at LeaveOneLightOn.org/keep-the-light-on.html. Leave One Light On is a Texas nonprofit corporation; federal 501(c)(3) recognition has not been obtained, and no promise of tax deductibility is made.”

## Final activation checklist

1. **Website review:** Approve the exact PR and book draft. Merge/publish only with separate authorization. Verify live routes and labels after deployment. Website copy and private merchandise previews do not depend on a particular bank.
2. **LOLO financial verification:** Confirm the nonprofit’s dedicated bank, provider legal entity, verification and payout eligibility in the correct LOLO account. Confirm separation from TJW/personal funds and responsible board oversight. This assignment makes no changes to those records.
3. **Contributions:** Approve amounts and frequency; inspect the preserved existing contribution route and current provider terms. Verify legal name, currency, receipts, cancellation and success/failure handling. Configure only the approved LOLO one-time/monthly options. Use provider test mode or supported nonfinancial verification under separate authorization before any owner-authorized live test.
4. **Merchandise:** Verify Printful legal/payout eligibility; approve final variants, retail/margins, shipping, taxes, customer contact and policies. Reuse the six templates. Publish only when authorized; confirm disabled checkout before the separate sales activation. Then enable and test only within the owner’s final authorization; do not make real purchases by default.
5. **Story intake:** Complete the existing staging/security/retention gates separately. Financial readiness does not authorize opening submissions.
6. **Communications:** Announce only features actually available after final checks. No campaign was sent or scheduled here.

The first release can publish the approved website with merchandise and monthly support still in preparation. It must preserve the owner-authorized existing contribution path unless the owner approves a documented change. No new campaign should invite payments until LOLO’s account eligibility is verified.

## Consolidated verification

- PASS: structural accessibility across 135 public HTML files, site integrity across 3,010 internal HTML references, discoverability baseline, Light Board privacy/structure across six pages, browser-script syntax and `git diff --check`.
- Two pre-existing heading-jump warnings remain in `light-board/index.html` and `light-board/archive.html`; those preserved pages were not edited.
- PASS: existing Stripe embed is byte-identical to main; exactly six merchandise cards; no shop/cart/checkout or payment control on the homepage; queue/admin/config/JavaScript/monthly-page/CSP files unchanged.
- PASS: changed pages render without horizontal overflow in 390px and 1280px review frames (375px and 1265px usable widths with scrollbars). Homepage desktop and phone, gallery, support and action layouts were visually inspected. These are browser responsive-frame checks, not physical-device testing.
- PASS: all six supplier mockups loaded at 400px intrinsic width and 200px rendered width. Hero uses the existing window artwork; headings use the established reading-friendly sans-serif style.
- PASS: selected live destinations (full founding story, Right on Time, reviewed organizations, Share a Light and monthly invitation) returned HTTP 200.
- PASS: book draft saved and reloaded as Draft with the two intended cross-links. Authenticated preview works; connector/Jetpack connection warning remains unresolved and was not changed.
- LIMIT: no local PHP runtime. No production story was submitted. Prior executable queue tests are recorded as passing in the existing release review, but this finishing assignment does not replace the outstanding database/security/retention staging checks.
- LIMIT: no real purchase or contribution, no new payment object, recurring enrollment, provider-identity edit, banking change or payout activation. Correct settlement and receipts remain unverified.

Browser preview is temporary and not a hosted draft release. A self-contained owner review accompanies this report; its payment widget is intentionally inert. The repository source preserves the existing Stripe embed unchanged.
