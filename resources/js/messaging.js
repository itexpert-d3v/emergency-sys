const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

const baseHeaders = {
  Accept: 'application/json',
  'Content-Type': 'application/json',
  'X-CSRF-TOKEN': csrf,
};

function renderMessages(panel, messages) {
  const list = panel.querySelector('[data-message-list]');
  const empty = panel.querySelector('[data-message-empty]');
  if (!list) return;

  list.replaceChildren(
    ...messages.map((m) => {
      const li = document.createElement('li');
      const isStaff = m.sender_type === 'staff';
      li.className =
        'rounded-xl px-3 py-2 text-sm ' +
        (isStaff
          ? 'bg-blue-50 text-blue-900 dark:bg-blue-500/10 dark:text-blue-200'
          : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100');

      const who = document.createElement('p');
      who.className = 'text-xs font-bold uppercase tracking-wide opacity-70';
      who.textContent = isStaff ? 'Staff' : 'Reporter';

      const body = document.createElement('p');
      body.textContent = m.body; // textContent, never innerHTML

      li.append(who, body);
      return li;
    })
  );

  empty?.classList.toggle('hidden', messages.length > 0);
  list.scrollTop = list.scrollHeight;
}

function showAlert(panel, text) {
  const box = panel.querySelector('[data-message-alert]');
  if (!box) return;
  box.textContent = text;
  box.classList.remove('hidden');
}

function hideAlert(panel) {
  panel.querySelector('[data-message-alert]')?.classList.add('hidden');
}

// host: optional <details> element. When given, the thread loads and polls only while it is open.
function setupThread(panel, url, host = null) {
  const form = panel.querySelector('[data-message-form]');
  if (!form) return;

  const load = async () => {
    try {
      const res = await fetch(url, { headers: baseHeaders });
      if (!res.ok) return;
      const data = await res.json();
      renderMessages(panel, data.messages);
      form.classList.toggle('hidden', !!data.thread_closed);
      if (data.thread_closed) showAlert(panel, 'This thread has been closed.');
    } catch {
      /* ignore polling errors */
    }
  };

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const field = form.elements.body;
    const body = field.value.trim();
    if (!body) return;

    const payload = { body };
    const closeBox = form.elements.close_thread;
    if (closeBox) payload.close_thread = closeBox.checked;

    const button = form.querySelector('[type="submit"]');
    if (button) button.disabled = true;

    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: baseHeaders,
        body: JSON.stringify(payload),
      });

      if (res.ok) {
        form.reset();
        hideAlert(panel);
        await load();
      } else {
        const err = await res.json().catch(() => ({}));
        const firstFieldError = err.errors ? Object.values(err.errors)[0]?.[0] : null;
        showAlert(panel, err.error || firstFieldError || err.message || 'Message could not be sent.');
      }
    } catch {
      showAlert(panel, 'Network error. Please try again.');
    } finally {
      if (button) button.disabled = false;
    }
  });

  let timer = null;
  const start = () => {
    if (timer) return;
    load();
    timer = setInterval(load, 15000);
  };
  const stop = () => {
    clearInterval(timer);
    timer = null;
  };

  if (host) {
    host.addEventListener('toggle', () => (host.open ? start() : stop()));
    if (host.open) start();
  } else {
    start();
  }
}

// Reporter side (tracking page)
document.querySelectorAll('[data-message-panel]').forEach((panel) => {
  const token = panel.dataset.token;
  if (token) setupThread(panel, `/track/${encodeURIComponent(token)}/messages`);
});

// Staff side (admin queue): each panel needs data-report-id
document.querySelectorAll('[data-staff-message-panel]').forEach((panel) => {
  const id = panel.dataset.reportId;
  if (id) setupThread(panel, `/staff/reports/${encodeURIComponent(id)}/messages`, panel.closest('details'));
});