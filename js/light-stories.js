document.addEventListener('DOMContentLoaded', () => {
  'use strict';
  const root = document.querySelector('.light-experience');
  if (!root) return;
  const node = (tag, text, cls) => {
    const el = document.createElement(tag);
    if (text !== undefined) el.textContent = text;
    if (cls) el.className = cls;
    return el;
  };
  const request = async (url, form) => {
    const response = await fetch(url, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, cache: 'no-store', credentials: 'same-origin' });
    const data = await response.json();
    if (!response.ok || !data.ok) throw new Error(data.error || 'This is temporarily unavailable. Please try again later.');
    return data;
  };
  const feed = root.querySelector('[data-story-feed]');
  if (feed) {
    const filter = root.querySelector('[data-story-filter]');
    const more = root.querySelector('[data-more-stories]');
    const status = root.querySelector('[data-feed-status]');
    let cursor = null;
    let generation = 0;
    const load = async (append = false) => {
      const current = ++generation;
      const query = new URLSearchParams();
      if (filter?.value) query.set('perspective', filter.value);
      if (append && cursor) query.set('cursor', cursor);
      more.disabled = true;
      status.textContent = 'Loading community stories…';
      try {
        const response = await fetch('/api/light-board/feed.php?' + query, { cache: 'no-store' });
        const data = await response.json();
        if (!response.ok || !data.ok) throw new Error();
        if (current !== generation) return;
        if (!append) feed.replaceChildren();
        for (const item of data.items) {
          const article = node('article', undefined, 'light-story');
          article.append(node('p', item.label + ' · Community account', 'light-kicker'));
          article.append(node('p', item.text));
          if (item.reflection) article.append(node('p', item.reflection));
          const link = node('a', 'Read and share this Light →');
          link.href = '/light-board/story.php?id=' + encodeURIComponent(item.id);
          article.append(link);
          feed.append(article);
        }
        cursor = data.next_cursor;
        more.hidden = !cursor;
        status.textContent = !feed.children.length
          ? (data.accepting ? 'Your story could be the first community Light here. Every submission waits for review.' : 'Community submissions are in preparation. You can still explore Ted’s reflection and this month’s invitation.')
          : 'Community accounts, reviewed for suitability and privacy. Not independently verified.';
      } catch (error) {
        if (current !== generation) return;
        more.hidden = true;
        status.textContent = 'Community stories are temporarily unavailable. Please try again later.';
      } finally {
        if (current === generation) more.disabled = false;
      }
    };
    filter?.addEventListener('change', () => load());
    more.addEventListener('click', () => load(true));
    load();
  }
  const form = root.querySelector('[data-light-submit]');
  if (form) {
    const intakeStatus = root.querySelector('[data-intake-status]');
    // The server switch and database readiness decide whether intake opens.
    // Keep the form hidden on unavailable or unconfigured environments.
    (async () => {
      try {
        const response = await fetch('/api/light-board/feed.php', { cache: 'no-store' });
        const availability = await response.json();
        if (!response.ok || !availability.ok) throw new Error();
        form.hidden = !availability.accepting;
        intakeStatus.textContent = availability.accepting
          ? 'Every story waits for human review before publication.'
          : 'Community story submissions are in preparation. Please return soon. You can still read a Light or send a private Contact message.';
      } catch (e) {
        form.hidden = true;
        intakeStatus.textContent = 'Story submissions are temporarily unavailable. Please return later; nothing has been sent.';
      }
    })();
    form.querySelector('[name=started_at]').value = String(Math.floor(Date.now() / 1000));
    const error = root.querySelector('[data-submit-error]');
    const button = form.querySelector('button[type=submit]');
    const input = form.querySelector('[name=story]');
    const counter = root.querySelector('[data-story-count]');
    input.addEventListener('input', () => { counter.textContent = String(input.value.length); });
    form.addEventListener('submit', async event => {
      event.preventDefault();
      if (!form.reportValidity()) return;
      error.textContent = '';
      button.disabled = true;
      try {
        const data = await request(form.action, form);
        form.hidden = true;
        root.querySelector('[data-private-receipt]').textContent = data.receipt;
        const success = root.querySelector('[data-submit-success]');
        success.hidden = false;
        success.focus();
        form.reset();
      } catch (e) {
        error.textContent = e instanceof SyntaxError ? 'We could not confirm receipt. Please save your text and try again later.' : e.message;
      } finally { button.disabled = false; }
    });
  }
  const statusForm = root.querySelector('[data-light-status]');
  if (statusForm) {
    const result = root.querySelector('[data-story-status]');
    const message = root.querySelector('[data-status-error]');
    const withdraw = root.querySelector('[data-withdraw-story]');
    const confirm = root.querySelector('[data-confirm-withdraw]');
    const labels = { pending: 'Waiting for review', needs_changes: 'Clarification needed', approved: 'Published anonymously', declined: 'Not selected for publication', withdrawn: 'Withdrawn' };
    statusForm.addEventListener('input', () => { result.hidden = true; confirm.hidden = true; });
    const lookup = async action => {
      message.textContent = '';
      statusForm.querySelector('[name=action]').value = action;
      const buttons = root.querySelectorAll('button');
      buttons.forEach(b => { b.disabled = true; });
      try {
        const data = await request(statusForm.action, statusForm);
        result.replaceChildren(node('h2', labels[data.state] || 'Under review'));
        if (data.review_note) result.append(node('p', data.review_note, 'light-text'));
        if (data.state === 'needs_changes') result.append(node('p', 'You can withdraw this entry and submit revised wording. Your original entry will stay private.'));
        if (data.public_url) {
          const link = node('a', 'Open my published story →');
          // Construct the known local route; never trust an arbitrary returned URL.
          const id = new URL(data.public_url, location.origin).searchParams.get('id');
          if (/^[a-f0-9]{24}$/.test(id || '')) { link.href = '/light-board/story.php?id=' + id; result.append(link); }
        }
        result.hidden = false;
        withdraw.hidden = data.state === 'withdrawn';
        confirm.hidden = true;
      } catch (e) { message.textContent = e instanceof SyntaxError ? 'We could not check your story. Please try again later.' : e.message; }
      finally { buttons.forEach(b => { b.disabled = false; }); }
    };
    statusForm.addEventListener('submit', e => { e.preventDefault(); if (statusForm.reportValidity()) lookup('status'); });
    withdraw.addEventListener('click', () => { confirm.hidden = false; });
    root.querySelector('[data-final-withdraw]').addEventListener('click', () => lookup('withdraw'));
    root.querySelector('[data-cancel-withdraw]').addEventListener('click', () => { confirm.hidden = true; });
  }
  const share = root.querySelector('[data-share-story]');
  if (share) {
    const status = root.querySelector('[data-share-status]');
    const url = root.querySelector('link[rel=canonical]')?.href || document.querySelector('link[rel=canonical]')?.href || location.href;
    share.addEventListener('click', async () => {
      try {
        if (navigator.share) { await navigator.share({ title: 'A Light On Story · Leave One Light On', url }); status.textContent = 'Share options opened.'; }
        else { await navigator.clipboard.writeText(url); status.textContent = 'Story link copied.'; }
      } catch (error) { if (error.name !== 'AbortError') status.textContent = 'Use the story link below to share.'; }
    });
  }
});
