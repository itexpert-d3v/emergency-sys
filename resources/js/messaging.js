/**
 * messaging.js
 *
 * Handles the message thread on:
 *   - Reporter side: tracking/show page  → [data-message-panel]
 *                     routes: GET/POST /track/{token}/messages
 *   - Staff side:    operations report   → [data-staff-message-panel]
 *                     routes: GET/POST /reports/{report}/messages
 *
 * SECURITY NOTES — read before editing
 *
 * DO NOT put auth tokens in localStorage, sessionStorage, or IndexedDB.
 * This file intentionally reads the tracking token from the URL and passes
 * it in the X-Tracking-Token header on every fetch. Nothing is persisted
 * client-side.
 *
 *   - Reporter token: read from `?token=` in the URL, or from
 *     [data-message-panel]@data-token (server-rendered). Ephemeral.
 *   - Staff session: HttpOnly `laravel_session` cookie. JS cannot read it.
 *   - CSRF: <meta name="csrf-token"> in <head>. Never stored.
 */

const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

const getHeaders = (token) => {
    const h = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
    if (csrf) h['X-CSRF-TOKEN'] = csrf;
    if (token) h['X-Tracking-Token'] = token;
    return h;
};

const postHeaders = (token) => ({
    ...getHeaders(token),
    'Content-Type': 'application/json',
});

/**
 * Prefer the token from the URL (?token=...) over the DOM attribute.
 * Both are ephemeral; the URL is the canonical source and is scrubbed
 * immediately after reading.
 */
function getTokenFromUrl() {
    try {
        return new URLSearchParams(window.location.search).get('token') || '';
    } catch {
        return '';
    }
}

/**
 * Remove ?token= from the address bar after the page has read it, so it
 * doesn't linger in browser history, bookmarks, or shared-link previews.
 * The server already sets Referrer-Policy: no-referrer, so this is
 * belt-and-braces.
 */
function scrubTokenFromUrl() {
    try {
        const url = new URL(window.location.href);
        if (!url.searchParams.has('token')) return;
        url.searchParams.delete('token');
        window.history.replaceState({}, '', url.toString());
    } catch {
        /* no-op */
    }
}

function formatTime(iso) {
    if (!iso) return '';
    try {
        return new Date(iso).toLocaleString(undefined, {
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        });
    } catch {
        return '';
    }
}

function renderMessages(panel, messages) {
    const list = panel.querySelector('[data-message-list]');
    const empty = panel.querySelector('[data-message-empty]');
    if (!list) return;

    list.replaceChildren(
        ...messages.map((m) => {
            const isStaff = m.sender_type === 'staff';

            const li = document.createElement('li');
            li.className = 'chat-msg ' + (isStaff ? 'chat-msg--staff' : 'chat-msg--reporter');
            li.dataset.sender = m.sender_type || 'reporter';

            const who = document.createElement('span');
            who.className = 'chat-msg__who';
            who.textContent = isStaff ? 'Staff' : 'You';

            const body = document.createElement('p');
            body.className = 'chat-msg__body';
            body.textContent = m.body; // textContent — never innerHTML

            const time = document.createElement('time');
            time.className = 'chat-msg__time';
            if (m.created_at) {
                time.dateTime = m.created_at;
                time.textContent = formatTime(m.created_at);
            }

            li.append(who, body, time);
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

function applyClosedState(panel, closed) {
    const form = panel.querySelector('[data-message-form]');
    if (!form) return;

    panel.dataset.closed = closed ? 'true' : 'false';
    form.classList.toggle('hidden', closed);
    form.querySelectorAll('input, textarea, button').forEach((el) => {
        el.disabled = closed;
    });

    if (closed) {
        showAlert(panel, 'This message thread has been closed.');
    } else {
        hideAlert(panel);
    }
}

/**
 * host: optional <details> element. When given, the thread loads and polls
 * only while it is open.
 *
 * options.token: tracking token (reporter side only)
 */
function setupThread(panel, url, host = null, options = {}) {
    const form = panel.querySelector('[data-message-form]');
    if (!form) {
        console.warn('[messaging] panel has no [data-message-form]', panel);
        return;
    }

    const token = options.token ?? panel.dataset.token ?? '';

    // Reflect any closed state Blade already rendered before the first fetch.
    if (panel.dataset.closed === 'true') {
        applyClosedState(panel, true);
    }

    const load = async () => {
        try {
            const res = await fetch(url, {
                headers: getHeaders(token),
                cache: 'no-store',
            });

            if (!res.ok) {
                if (res.status === 404) {
                    console.warn('[messaging] thread not found', url);
                } else if (res.status === 429) {
                    console.warn('[messaging] read rate-limited', url);
                } else {
                    console.warn('[messaging] load failed', res.status, url);
                }
                return;
            }

            const data = await res.json();
            renderMessages(panel, data.messages ?? []);
            applyClosedState(panel, !!data.thread_closed);
        } catch (err) {
            console.warn('[messaging] poll failed', err);
        }
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const field = form.elements.body;
        const body = field?.value.trim() ?? '';
        if (!body) {
            showAlert(panel, 'Please type a message first.');
            field?.focus();
            return;
        }

        const payload = { body };

        const button = form.querySelector('[type="submit"]');
        if (button) button.disabled = true;

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: postHeaders(token),
                body: JSON.stringify(payload),
            });

            if (res.ok) {
                form.reset();
                hideAlert(panel);
                await load();
            } else if (res.status === 429) {
                const retry = parseInt(res.headers.get('Retry-After') || '0', 10);
                const wait = retry > 0
                    ? ` Please wait ${retry} second${retry === 1 ? '' : 's'}.`
                    : ' Please wait a moment.';
                showAlert(panel, 'You are sending messages too quickly.' + wait);
            } else if (res.status === 422) {
                const err = await res.json().catch(() => ({}));
                const firstFieldError = err.errors
                    ? Object.values(err.errors)[0]?.[0]
                    : null;
                showAlert(
                    panel,
                    firstFieldError || err.error || err.message || 'Please check your message and try again.'
                );
            } else if (res.status === 403) {
                showAlert(panel, 'This message thread has been closed.');
                applyClosedState(panel, true);
            } else {
                const err = await res.json().catch(() => ({}));
                const firstFieldError = err.errors
                    ? Object.values(err.errors)[0]?.[0]
                    : null;
                showAlert(
                    panel,
                    err.error ||
                        firstFieldError ||
                        err.message ||
                        `Message could not be sent (${res.status}).`
                );
            }
        } catch (err) {
            console.warn('[messaging] send failed', err);
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

// ---------------------------------------------------------------------------
// Reporter side — routes are GET/POST /track/{token}/messages
// ---------------------------------------------------------------------------
document.querySelectorAll('[data-message-panel]').forEach((panel) => {
    const token = getTokenFromUrl() || panel.dataset.token || '';
    if (!token) {
        console.warn('[messaging] reporter panel has no token — thread disabled');
        return;
    }
    scrubTokenFromUrl();
    setupThread(panel, `/track/${encodeURIComponent(token)}/messages`, null, { token });
});

// ---------------------------------------------------------------------------
// Operations (staff) side — routes are GET/POST /reports/{report}/messages
// ---------------------------------------------------------------------------
document.querySelectorAll('[data-staff-message-panel]').forEach((panel) => {
    const id = panel.dataset.reportId;
    if (!id) return;
    setupThread(
        panel,
        `/reports/${encodeURIComponent(id)}/messages`,
        panel.closest('details'),
        {}
    );
});