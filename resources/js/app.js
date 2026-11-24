import './bootstrap';
import './report-form';
import './messaging';
import './pwa';
import './report-draft';

const themeKey = 'emergency-sys-theme';
const root = document.documentElement;

const savedTheme = () => {
    try {
        const value = localStorage.getItem(themeKey);

        return value === 'dark' || value === 'light' ? value : 'system';
    } catch {
        return 'system';
    }
};

const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)');

const applyTheme = (mode) => {
    const isDark = mode === 'system' ? systemPrefersDark.matches : mode === 'dark';

    root.classList.toggle('dark', isDark);
    root.style.colorScheme = isDark ? 'dark' : 'light';
    document.querySelectorAll('[data-theme-toggle]').forEach((control) => {
        const action = isDark ? 'Switch to light mode' : 'Switch to dark mode';
        control.setAttribute('aria-label', action);
        control.setAttribute('title', action);
    });
    document.querySelectorAll('[data-appearance-choice]').forEach((control) => {
        const selected = control.dataset.appearanceChoice === mode;
        control.setAttribute('aria-pressed', String(selected));
        control.classList.toggle('is-selected', selected);
    });
};

applyTheme(savedTheme());
systemPrefersDark.addEventListener('change', () => {
    if (savedTheme() === 'system') applyTheme('system');
});

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) return;

    const control = event.target.closest('[data-theme-toggle]');

    if (!control) return;

    const nextTheme = root.classList.contains('dark') ? 'light' : 'dark';
    applyTheme(nextTheme);

    try {
        localStorage.setItem(themeKey, nextTheme);
    } catch {
        // Theme switching still works when browser storage is unavailable.
    }
});

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) return;

    const control = event.target.closest('[data-appearance-choice]');
    if (!control) return;

    const mode = control.dataset.appearanceChoice;
    if (!['light', 'dark', 'system'].includes(mode)) return;

    try {
        if (mode === 'system') localStorage.removeItem(themeKey);
        else localStorage.setItem(themeKey, mode);
    } catch {
        // The current page still applies the selected mode without storage.
    }
    applyTheme(mode);
});

const formatStatus = (status) => status.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

const formatTimestamp = (timestamp) => new Intl.DateTimeFormat(root.lang || undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
}).format(new Date(timestamp));

const renderTimeline = (timeline) => {
    const list = document.querySelector('[data-status-timeline]');

    if (!list) return;

    list.replaceChildren(...timeline.map((entry) => {
        const item = document.createElement('li');
        item.className = 'relative';

        const marker = document.createElement('span');
        marker.className = 'absolute -left-[1.65rem] top-1 h-3 w-3 rounded-full bg-amber-400 ring-4 ring-amber-100 dark:ring-amber-500/20';

        const label = document.createElement('p');
        label.className = 'font-bold text-slate-900 dark:text-white';
        label.textContent = entry.label || formatStatus(entry.status);

        const timestamp = document.createElement('p');
        timestamp.className = 'mt-1 text-sm text-slate-500 dark:text-slate-400';
        timestamp.textContent = entry.timestamp ? formatTimestamp(entry.timestamp) : '';

        item.append(marker, label, timestamp);

        return item;
    }));
};

const connectTrackingStatus = () => {
    const widget = document.querySelector('[data-status-widget]');
    const refreshNotice = document.querySelector('#status-refresh');

    if (!widget || !refreshNotice) return;

    const reference = widget.dataset.reference;
    const token = widget.dataset.token;

    if (!reference || !token) return;

    const refresh = async () => {
        try {
            const response = await fetch(`/api/reports/${encodeURIComponent(reference)}/status`, {
                headers: { 'X-Tracking-Token': token, Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error('Tracking status could not be refreshed.');

            const payload = await response.json();
            const status = document.querySelector('#report-status');

            if (status) status.textContent = formatStatus(payload.status);
            if (Array.isArray(payload.timeline)) renderTimeline(payload.timeline);
            if (Array.isArray(payload.public_updates)) renderPublicUpdates(payload.public_updates);
            refreshNotice.textContent = `Updated ${new Intl.DateTimeFormat(root.lang || undefined, { timeStyle: 'short' }).format(new Date())}.`;
        } catch {
            refreshNotice.textContent = 'Unable to refresh status. Reload this page to try again.';
        }
    };

    refresh();
    window.setInterval(() => {
        if (document.visibilityState === 'visible') refresh();
    }, 30000);
};

connectTrackingStatus();

const renderPublicUpdates = (updates) => {
    const list = document.querySelector('[data-public-updates]');

    if (!list) return;

    list.replaceChildren(...updates.map((update) => {
        const item = document.createElement('li');
        const body = document.createElement('p');
        body.className = 'font-semibold text-slate-800 dark:text-slate-100';
        body.textContent = update.body;
        const timestamp = document.createElement('p');
        timestamp.className = 'text-xs text-slate-500 dark:text-slate-400';
        timestamp.textContent = update.created_at ? formatTimestamp(update.created_at) : '';
        item.append(body, timestamp);

        return item;
    }));

    if (updates.length === 0) {
        const empty = document.createElement('li');
        empty.dataset.publicUpdatesEmpty = '';
        empty.textContent = 'No response updates yet.';
        list.append(empty);
    }
};

const connectReportProgress = () => {
    const form = document.querySelector('#report-form');
    const bar = document.querySelector('[data-report-progress-bar]');
    const label = document.querySelector('[data-report-progress-label]');

    if (!form || !bar || !label) return;

    const description = form.querySelector('#description');
    const location = form.querySelector('#location_text');
    const latitude = form.querySelector('#latitude');
    const longitude = form.querySelector('#longitude');
    const name = form.querySelector('#reporter_name');
    const contact = form.querySelector('#contact_number');

    const update = () => {
        const completed = [
            description.value.trim().length >= Number(description.minLength),
            location.value.trim() !== '' || (latitude.value !== '' && longitude.value !== ''),
            name.value.trim() !== '',
            contact.checkValidity(),
        ].filter(Boolean).length;
        const percent = Math.round(completed / 4 * 100);

        bar.style.width = `${percent}%`;
        bar.parentElement.setAttribute('aria-valuenow', String(percent));
        label.textContent = `${completed} of 4 required details complete`;
    };

    form.addEventListener('input', update);
    form.addEventListener('change', update);
    update();
};

connectReportProgress();

const sidebar = document.querySelector('#operations-sidebar');
const mobileMenuToggle = document.querySelector('[data-mobile-menu-toggle]');
const sidebarBackdrop = document.querySelector('[data-sidebar-backdrop]');

const isNarrow = () => window.matchMedia('(max-width: 639px)').matches;

const setMobileDrawer = (open) => {
    if (!sidebar) return;
    document.body.classList.toggle('mobile-menu-open', open);
    mobileMenuToggle?.setAttribute('aria-expanded', String(open));
    mobileMenuToggle?.setAttribute('aria-label', open ? 'Close operations navigation' : 'Open operations navigation');

    if (open) {
        const focusable = sidebar.querySelector('a, button');
        window.requestAnimationFrame(() => focusable?.focus());
    } else if (isNarrow()) {
        mobileMenuToggle?.focus();
    }
};

try {
    localStorage.removeItem('emergency-sys-admin-sidebar');
} catch {}
document.body.classList.remove('sidebar-collapsed');

mobileMenuToggle?.addEventListener('click', () => {
    setMobileDrawer(!document.body.classList.contains('mobile-menu-open'));
});
sidebarBackdrop?.addEventListener('click', () => setMobileDrawer(false));

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && document.body.classList.contains('mobile-menu-open')) {
        setMobileDrawer(false);
    }

    if (event.key === 'Tab' && document.body.classList.contains('mobile-menu-open') && sidebar) {
        const focusable = [...sidebar.querySelectorAll('a[href], button:not([disabled])')]
            .filter((element) => element.offsetParent !== null);
        if (!focusable.length) return;

        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }
});

window.matchMedia('(max-width: 639px)').addEventListener('change', (event) => {
    if (!event.matches) setMobileDrawer(false);
});
