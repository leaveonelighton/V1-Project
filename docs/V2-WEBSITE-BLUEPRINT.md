# Leave One Light On — Website V2 Blueprint

**Baseline:** July 25, 2026  
**Branch:** `v2-development`  
**Rule:** The live `main` branch remains untouched until V2 is complete, tested, and approved.

## V2 objective

Transform the current collection of individual pages into one coherent visitor experience: a welcoming front porch for seekers, a living room for story and encouragement, and a clear path into deeper Christian formation for those who choose it.

## Governing communication principle

We never disguise who we are, but we intentionally communicate in language appropriate to where people are.

Public pages lead with hope, welcome, compassion, hospitality, stewardship, purpose, encouragement, and belonging. Explicit Christian language appears naturally and clearly on deeper formation, church, and discipleship pages.

**Communication rhythm:** Lead with hope. Build through relationship. Reveal the foundation. Point to Christ.

## Three universal calls to action

1. **Read the Story**
2. **Walk With Us Today**
3. **Keep the Light On**

`Looking for Hope` remains visible from every page and is never hidden in a dropdown.

## Recommended V2 navigation

Home | Our Story | The House | The Gorman Legacy | Books | Walk With Us | Looking for Hope | Keep the Light On | Contact

## Homepage V2 order

1. Hero: “Sometimes the world does not need another loud voice. Sometimes it just needs one light left on.”
2. Welcome: “If you are searching, hurting, curious, or already following Jesus, you are welcome here. Start wherever you are.”
3. Origin story: one house changed Ted’s life forever.
4. The House: it was not the size of the house that changed lives; it was the people inside it.
5. Practical invitation: Notice. Care. Wonder. Follow.
6. Light Keepers: see, serve, steward, strengthen, and share.
7. Books: present a connected library rather than unrelated products.
8. Keep the Light On: explain what Stripe support sustains; do not present Stripe as book checkout.
9. Closing: “Someone, somewhere, is looking for a light. Perhaps yours is the one they will see.”

## Page direction

- **Our Story:** Connect the orphanage, separation, arrival at the Gorman home, learning to belong, and the responsibility to pass the light forward.
- **The House:** Make 5 Cedar Hill Road a character through the porch, open door, table, empty chair, window, and sending imagery.
- **The Gorman Legacy:** Present Peg and Jerry as ordinary people practicing extraordinary compassion and stewarding possibility.
- **Books:** Separate available books from future works. Keep Amazon and InkFluence links clear. Stripe remains support-only.
- **Walk With Us:** Offer practical actions: notice someone, write a note, share a meal, make a call, mentor, serve locally, support foster families, or help someone find professional assistance.
- **Looking for Hope:** Calm, direct crisis and assistance resources with clear boundaries that the ministry is not an emergency or clinical service.
- **Keep the Light On:** A dedicated Stripe support page explaining what support sustains without promises that cannot be guaranteed.
- **Contact:** Story sharing, speaking, partnerships, church use, media/film inquiries, and general questions using `info@leaveonelighton.org`.

## Brand system

- Typography: Playfair Display headings; Lato body; no cursive fonts.
- Palette: navy `#24364D`, ivory `#F7F3ED`, gold `#D4A52C`.
- Imagery: porch light, window, open door, kitchen table, empty chair, letters, old photographs, rain on glass, morning light.
- Tone: warm, dignified, human, hopeful, uncluttered.
- Mobile-first: navigation, help links, purchasing links, and Stripe support must work cleanly on phones.

## Build sequence

1. Normalize the shared header, navigation, footer, metadata, accessibility labels, and CSS tokens.
2. Rebuild `index.html` with the V2 narrative order and three-CTA system.
3. Add `walk-with-us.html` as the practical action center.
4. Revise story, house, Gorman, books, hope, support, movement, and contact pages.
5. Test every link, image, email, purchase link, Stripe link, mobile layout, title, meta description, heading structure, keyboard path, and contrast.
6. Prepare a release candidate and preserve a rollback point before merging to `main`.

## Acceptance standard

- Leave One Light On branding is consistent everywhere.
- Navigation and footer are identical across pages.
- Calls to action use the approved vocabulary.
- Books include clear Amazon and InkFluence links.
- Stripe is support-only.
- Looking for Hope is prominent and responsible.
- Public language welcomes first; deeper Christian pages remain clear.
- Mobile, accessibility, and all links pass review.
- Release notes and rollback instructions are preserved.

## Ministry DNA

**Motto:** Steward Well. Leave One Light On.

**Legacy line:** Steward what you have been given. Make room for one more. Leave one light on.

**Reputation goal:** “Every person I met through Leave One Light On made me feel seen.”

This file is the preserved V2 baseline. Future changes should be recorded in release notes rather than silently replacing the original intent.