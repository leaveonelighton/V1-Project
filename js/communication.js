(() => {
  'use strict';

  const startedAt = document.querySelector('input[name="started_at"]');
  if (startedAt && !startedAt.value) {
    startedAt.value = String(Math.floor(Date.now() / 1000));
  }

  const responseRadios = Array.from(document.querySelectorAll('input[name="response_method"]'));
  const contactWrap = document.querySelector('[data-contact-wrap]');
  const contactInput = document.querySelector('[data-contact-input]');
  const contactLabel = document.querySelector('[data-contact-label]');
  const contactHelp = document.querySelector('[data-contact-help]');

  function syncContactField() {
    if (!contactWrap || !contactInput) return;
    const selected = responseRadios.find((radio) => radio.checked)?.value || 'private';
    const needsContact = ['email', 'text', 'phone'].includes(selected);
    contactWrap.hidden = !needsContact;
    contactInput.required = needsContact;

    if (selected === 'email') {
      contactInput.type = 'email';
      contactInput.autocomplete = 'email';
      if (contactLabel) contactLabel.textContent = 'Email address';
      if (contactHelp) contactHelp.textContent = 'Used only to respond to this message.';
    } else if (selected === 'text') {
      contactInput.type = 'tel';
      contactInput.autocomplete = 'tel';
      if (contactLabel) contactLabel.textContent = 'Mobile number';
      if (contactHelp) contactHelp.textContent = 'Used only to text about this message.';
    } else if (selected === 'phone') {
      contactInput.type = 'tel';
      contactInput.autocomplete = 'tel';
      if (contactLabel) contactLabel.textContent = 'Phone number';
      if (contactHelp) contactHelp.textContent = 'Used only to call about this message.';
    } else {
      contactInput.value = '';
    }
  }

  responseRadios.forEach((radio) => radio.addEventListener('change', syncContactField));
  syncContactField();

  const sendForm = document.querySelector('[data-private-message-form]');
  const sendStatus = document.querySelector('[data-send-status]');
  const successPanel = document.querySelector('[data-success-panel]');

  if (sendForm) {
    sendForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (sendStatus) {
        sendStatus.textContent = 'Sending…';
        sendStatus.className = 'form-status';
      }

      try {
        const response = await fetch(sendForm.action, {
          method: 'POST',
          headers: { Accept: 'application/json', 'X-Requested-With': 'fetch' },
          body: new FormData(sendForm)
        });
        const data = await response.json();
        if (!response.ok || !data.ok) {
          throw new Error(data.error || 'We could not send the message.');
        }

        if (sendStatus) {
          sendStatus.textContent = data.message;
          sendStatus.className = 'form-status success';
        }
        if (successPanel) {
          const code = successPanel.querySelector('[data-private-code]');
          const reference = successPanel.querySelector('[data-reference]');
          const codeSection = successPanel.querySelector('[data-private-code-section]');
          if (code) code.textContent = data.private_code || '';
          if (reference) reference.textContent = data.reference || '';
          if (codeSection) codeSection.hidden = data.response_method === 'none';
          successPanel.hidden = false;
          successPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        sendForm.reset();
        syncContactField();
        if (startedAt) startedAt.value = String(Math.floor(Date.now() / 1000));
      } catch (error) {
        if (sendStatus) {
          sendStatus.textContent = error instanceof Error ? error.message : 'We could not send the message.';
          sendStatus.className = 'form-status error';
        }
      }
    });
  }

  const checkForm = document.querySelector('[data-check-response-form]');
  const checkStatus = document.querySelector('[data-check-status]');
  const responsePanel = document.querySelector('[data-response-panel]');
  const followUpForm = document.querySelector('[data-follow-up-form]');
  const followUpCode = document.querySelector('[data-follow-up-code]');
  const followUpStatus = document.querySelector('[data-follow-up-status]');
  let activePrivateCode = '';

  function formatSender(sender) {
    return sender === 'admin' ? 'Leave One Light On' : 'You';
  }

  function renderConversation(conversation) {
    if (!responsePanel) return;
    const heading = responsePanel.querySelector('[data-thread-reference]');
    const state = responsePanel.querySelector('[data-thread-status]');
    const thread = responsePanel.querySelector('[data-thread]');
    if (heading) heading.textContent = conversation.reference;
    if (state) state.textContent = conversation.status;
    if (followUpForm) followUpForm.hidden = conversation.status !== 'open';
    if (thread) {
      thread.replaceChildren();
      conversation.messages.forEach((message) => {
        const article = document.createElement('article');
        article.className = `message-card ${message.sender === 'admin' ? 'message-admin' : 'message-visitor'}`;
        const meta = document.createElement('p');
        meta.className = 'message-meta';
        const strong = document.createElement('strong');
        strong.textContent = formatSender(message.sender);
        meta.append(strong, document.createTextNode(` · ${message.created_at}`));
        const body = document.createElement('p');
        body.textContent = message.message;
        article.append(meta, body);
        thread.append(article);
      });
    }
    responsePanel.hidden = false;
  }

  async function loadConversation(privateCode) {
    const formData = new FormData();
    formData.set('private_code', privateCode);
    const response = await fetch('/api/messages/check.php', {
      method: 'POST',
      headers: { Accept: 'application/json', 'X-Requested-With': 'fetch' },
      body: formData
    });
    const data = await response.json();
    if (!response.ok || !data.ok) {
      throw new Error(data.error || 'We could not open that conversation.');
    }
    renderConversation(data.conversation);
    return data.conversation;
  }

  if (checkForm) {
    checkForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (checkStatus) {
        checkStatus.textContent = 'Checking…';
        checkStatus.className = 'form-status';
      }

      try {
        const formData = new FormData(checkForm);
        activePrivateCode = String(formData.get('private_code') || '');
        if (followUpCode) followUpCode.value = activePrivateCode;
        await loadConversation(activePrivateCode);
        if (checkStatus) {
          checkStatus.textContent = 'Conversation found.';
          checkStatus.className = 'form-status success';
        }
      } catch (error) {
        if (responsePanel) responsePanel.hidden = true;
        if (checkStatus) {
          checkStatus.textContent = error instanceof Error ? error.message : 'We could not open that conversation.';
          checkStatus.className = 'form-status error';
        }
      }
    });
  }

  if (followUpForm) {
    followUpForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (followUpStatus) {
        followUpStatus.textContent = 'Sending…';
        followUpStatus.className = 'form-status';
      }

      try {
        const formData = new FormData(followUpForm);
        formData.set('private_code', activePrivateCode || String(formData.get('private_code') || ''));
        const response = await fetch(followUpForm.action, {
          method: 'POST',
          headers: { Accept: 'application/json', 'X-Requested-With': 'fetch' },
          body: formData
        });
        const data = await response.json();
        if (!response.ok || !data.ok) {
          throw new Error(data.error || 'We could not add that message.');
        }
        const textarea = followUpForm.querySelector('textarea[name="message"]');
        if (textarea) textarea.value = '';
        await loadConversation(activePrivateCode);
        if (followUpStatus) {
          followUpStatus.textContent = 'Your follow-up was added.';
          followUpStatus.className = 'form-status success';
        }
      } catch (error) {
        if (followUpStatus) {
          followUpStatus.textContent = error instanceof Error ? error.message : 'We could not add that message.';
          followUpStatus.className = 'form-status error';
        }
      }
    });
  }
})();
