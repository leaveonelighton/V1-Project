<?php
declare(strict_types=1);
require dirname(__DIR__) . '/api/light-board/_service.php';
$intro = '<p><a href="https://thelightinthewindowbook.com/the-57/">Return to The Other 56</a></p><h1>Your story belongs to you.</h1><p>You may share your own Gorman experience, use a name or pseudonym, remain anonymous, or keep it private. There is no expectation that your experience matches anyone else’s.</p>';
$ready = false;
try {
    if (lb_gorman_enabled()) {
        // Fail closed if the additive schema has not been installed.
        lol_db()->query('SELECT attribution_mode, contributor_ciphertext, public_attribution FROM light_stories LIMIT 0');
        $ready = true;
    }
} catch (Throwable $e) { $ready = false; }
if (!$ready) lol_render_shell('The Other 56 — story sharing', $intro . '<p>Story submissions are not open yet. You do not need to share anything to be part of this history.</p>');
$started = time();
$form = <<<'HTML'
<form class="communication-form" method="post" action="/api/light-board/submit.php">
<input type="hidden" name="perspective" value="gorman">
<input type="hidden" name="started_at" value="__STARTED__">
<div hidden aria-hidden="true"><label for="website">Leave this field empty</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
<p><label for="contributor-name">Name or pseudonym (optional unless you choose public attribution)</label><input type="text" id="contributor-name" name="contributor_name" maxlength="100" autocomplete="off"></p>
<p><label for="private-contact">Private email (optional; never published)</label><input id="private-contact" type="email" name="private_contact" maxlength="254" autocomplete="email"></p>
<p><label for="attribution">If you consent to publication, how should you be credited?</label><select id="attribution" name="attribution_mode" required><option value="anonymous">Anonymous</option><option value="named">Use the name or pseudonym I entered</option></select></p>
<p><label for="gorman-story">Your own experience (10–2,500 characters)</label><textarea id="gorman-story" name="story" minlength="10" maxlength="2500" rows="12" required aria-describedby="story-care"></textarea></p>
<p id="story-care">Please avoid private contact details, addresses, and identifying details about other people. Tell only what you choose. Different memories and experiences are welcome.</p>
<p><label><input type="checkbox" name="website_consent" value="yes"> I consent to LOLO publishing this account on its website after owner moderation, using my attribution choice. Light privacy/readability edits may be made without changing my meaning. Substantive changes require my agreement. This does not authorize merchandise, advertising, newsletter or social-media reuse.</label></p>
<p>Leave publication consent unchecked for private review only. Nothing is published automatically. Publication is not guaranteed.</p>
<h2>Privacy and your choices</h2>
<p>The owner can read your submission and any optional contact details. Your original story, optional name and email are encrypted in storage. A public version includes only reviewed wording and your chosen public attribution; your email is never public. Spam protection uses a honeypot, timing check and rate limits.</p>
<p>Save the private receipt shown after sending. Use <a href="/light-board/my-story.html">Check my story</a> to check review status or withdraw it, including after publication. Withdrawal removes the hosted public copy immediately and prevents republication. Copies shared elsewhere may remain. We cannot recover a lost receipt; if you provided a private email, use <a href="/contact.html">Contact LOLO</a> from that address to request removal. We will verify the request privately.</p>
<p>Pending entries expire after 90 days without updates; declined or withdrawn entries after 30 days. Private originals and contact details for published entries are removed after 30 days. Approved public wording and attribution remain until removal. These retention periods require the scheduled cleanup to be active.</p>
<p><a href="/privacy.html">LOLO privacy policy</a> · <a href="/light-board/methodology.html">Review practices</a></p>
<button type="submit">Send privately for owner review</button>
</form>
HTML;
lol_render_shell('The Other 56 — share your experience', $intro . str_replace('__STARTED__', (string)$started, $form));
