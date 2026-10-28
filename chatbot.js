(() => {
  'use strict';

  const configElement = document.getElementById('bamstay-chat-config');
  const toggle = document.getElementById('bamstay-chat-toggle');
  const panel = document.getElementById('bamstay-chat-panel');
  const close = document.getElementById('bamstay-chat-close');
  const form = document.getElementById('bamstay-chat-form');
  const input = document.getElementById('bamstay-chat-input');
  const send = document.getElementById('bamstay-chat-send');
  const messages = document.getElementById('bamstay-chat-messages');
  const suggestions = messages?.querySelector('.chat-suggestions');
  if (!configElement || !toggle || !panel || !close || !form || !input || !send || !messages) return;

  let config;
  try {
    config = JSON.parse(configElement.textContent);
  } catch (error) {
    console.error('BAMStay Assistant configuration could not be read.', error);
    return;
  }

  if (typeof config.endpoint !== 'string' || typeof config.csrfToken !== 'string') {
    console.error('BAMStay Assistant configuration is incomplete.');
    return;
  }

  const conversation = [];
  let isSending = false;

  function addBubble(text, sender) {
    const bubble = document.createElement('div');
    bubble.className = `chat-bubble chat-bubble-${sender}`;
    bubble.textContent = text;
    messages.append(bubble);
    messages.scrollTop = messages.scrollHeight;
    return bubble;
  }

  function setOpen(open) {
    panel.hidden = !open;
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Close BAMStay Assistant' : 'Open BAMStay Assistant');
    if (open) input.focus();
    else toggle.focus();
  }

  toggle.addEventListener('click', () => setOpen(panel.hidden));
  close.addEventListener('click', () => setOpen(false));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !panel.hidden) setOpen(false);
  });

  suggestions?.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLButtonElement) || isSending) return;
    input.value = target.textContent.trim();
    suggestions.hidden = true;
    form.requestSubmit();
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (isSending) return;

    const message = input.value.trim();
    if (!message) {
      input.focus();
      return;
    }

    isSending = true;
    input.value = '';
    input.disabled = true;
    send.disabled = true;
    addBubble(message, 'user');
    const typing = document.createElement('div');
    typing.className = 'chat-bubble chat-bubble-assistant chat-typing';
    typing.setAttribute('role', 'status');
    typing.textContent = 'BAMStay Assistant is replying…';
    messages.append(typing);
    messages.scrollTop = messages.scrollHeight;

    try {
      const response = await fetch(config.endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-Token': config.csrfToken,
        },
        body: JSON.stringify({
          message,
          history: conversation.slice(-4),
        }),
        signal: AbortSignal.timeout(95000),
      });

      let result;
      try {
        result = await response.json();
      } catch {
        throw new Error('The assistant returned an unreadable response. Please try again.');
      }

      if (!response.ok || typeof result.reply !== 'string') {
        throw new Error(typeof result.error === 'string'
          ? result.error
          : 'The assistant could not answer right now. Please try again later.');
      }

      typing.remove();
      addBubble(result.reply, 'assistant');
      conversation.push(
        { role: 'user', content: message.slice(0, 500) },
        { role: 'assistant', content: result.reply.slice(0, 500) },
      );
      if (conversation.length > 4) conversation.splice(0, conversation.length - 4);
    } catch (error) {
      typing.remove();
      console.error('BAMStay Assistant request failed.', error);
      addBubble(error instanceof Error && error.name === 'TimeoutError'
        ? 'The assistant is taking longer than expected. Please try again.'
        : error instanceof Error
          ? error.message
          : 'The assistant is unavailable right now. Please try again later.', 'assistant');
    } finally {
      isSending = false;
      input.disabled = false;
      send.disabled = false;
      input.focus();
    }
  });
})();
