@php
    $toasts = [];
    $flash = session('toast');

    if (is_array($flash)) {
        $toasts[] = $flash;
    } elseif ($legacy = session('status')) {
        // Old-style flash: ->with('status', '...'). Still shown, as a toast.
        $toast = ['type' => 'success', 'title' => (string) $legacy];
        if (str_contains(strtolower((string) $legacy), 'archived') && \Illuminate\Support\Facades\Route::has('admin.news.index')) {
            $toast['action'] = ['label' => 'View archive', 'url' => route('admin.news.index', ['status' => 'archived'])];
        }
        $toasts[] = $toast;
    }

    if ($errors->any()) {
        $all = $errors->all();
        if (count($all) === 1) {
            $toasts[] = ['type' => 'error', 'title' => "That didn't work", 'message' => $all[0]];
        } else {
            $list = array_slice($all, 0, 4);
            if (count($all) > 4) {
                $list[] = 'and ' . (count($all) - 4) . ' more';
            }
            $toasts[] = ['type' => 'error', 'title' => 'Fix ' . count($all) . ' things and try again', 'list' => $list];
        }
    }

    $glyphs = ['success' => '✓', 'error' => '✕', 'warning' => '!', 'info' => 'i'];
@endphp

<style>
    .toast-stack { position: fixed; top: 1rem; right: 1rem; z-index: 2147483647; display: flex; flex-direction: column; gap: .625rem; width: min(24rem, calc(100vw - 2rem)); pointer-events: none; }
    .toast-stack .toast { --toast-accent: #0284c7; pointer-events: auto; position: relative; display: grid; grid-template-columns: auto 1fr auto; gap: .75rem; align-items: start; padding: .8rem .75rem .9rem 1rem; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; color: #0f172a; box-shadow: 0 10px 30px -8px rgba(15, 23, 42, .35); overflow: hidden; animation: toast-in .22s ease-out; }
    .toast-stack .toast::before { content: ""; position: absolute; top: 0; bottom: 0; left: 0; width: 4px; background: var(--toast-accent); }
    .toast-stack .toast-success { --toast-accent: #059669; }
    .toast-stack .toast-error { --toast-accent: #e11d48; }
    .toast-stack .toast-warning { --toast-accent: #d97706; }
    .toast-stack .toast-info { --toast-accent: #0284c7; }
    .toast-stack .toast-icon { display: grid; place-items: center; width: 1.5rem; height: 1.5rem; border-radius: 999px; background: var(--toast-accent); color: #fff; font-size: .8rem; font-weight: 900; line-height: 1; }
    .toast-stack .toast-title { margin: 0; font-size: .875rem; font-weight: 800; line-height: 1.3; color: inherit; }
    .toast-stack .toast-message { margin: .15rem 0 0; font-size: .8125rem; line-height: 1.4; color: #475569; }
    .toast-stack .toast-list { margin: .35rem 0 0; padding-left: 1rem; list-style: disc; font-size: .8125rem; line-height: 1.4; color: #475569; }
    .toast-stack .toast-action { display: inline-flex; align-items: center; min-height: 1.75rem; margin-top: .5rem; padding: .25rem .65rem; border: 1px solid var(--toast-accent); border-radius: 4px; background: transparent; color: var(--toast-accent); font-size: .75rem; font-weight: 800; line-height: 1.2; text-decoration: none; cursor: pointer; }
    .toast-stack .toast-action:hover { background: var(--toast-accent); color: #fff; }
    .toast-stack .toast-close { appearance: none; display: grid; place-items: center; width: 1.75rem; height: 1.75rem; min-height: 0; border: 0; background: transparent; color: #94a3b8; font-size: 1.1rem; line-height: 1; padding: 0; cursor: pointer; border-radius: 4px; }
    .toast-stack .toast-close:hover { color: #0f172a; background: #f1f5f9; }
    .toast-stack .toast-progress { position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: var(--toast-accent); opacity: .55; transform-origin: left; animation: toast-progress linear forwards; }
    .toast-stack .toast[data-duration="0"] .toast-progress { display: none; }
    .toast-stack .toast-paused .toast-progress { animation-play-state: paused; }
    .toast-stack .toast-leave { animation: toast-out .18s ease-in forwards; }
    html.dark .toast-stack .toast { background: #0f172a; color: #f1f5f9; border-color: #334155; }
    html.dark .toast-stack .toast-message, html.dark .toast-stack .toast-list { color: #94a3b8; }
    html.dark .toast-stack .toast-close:hover { color: #fff; background: #1e293b; }
    @keyframes toast-in { from { opacity: 0; transform: translateX(16px); } to { opacity: 1; transform: none; } }
    @keyframes toast-out { to { opacity: 0; transform: translateX(16px); } }
    @keyframes toast-progress { from { transform: scaleX(1); } to { transform: scaleX(0); } }
    @media (prefers-reduced-motion: reduce) { .toast-stack .toast, .toast-stack .toast-leave { animation: none; } .toast-stack .toast-progress { display: none; } }
</style>

<div class="toast-stack" id="toast-stack" aria-live="polite">
    @foreach ($toasts as $t)
        @php
            $type = in_array($t['type'] ?? 'info', ['success', 'error', 'warning', 'info'], true) ? $t['type'] : 'info';
            $duration = $type === 'error' ? 0 : (! empty($t['action']) ? 9000 : 5000);
            $action = $t['action'] ?? null;
        @endphp
        <div class="toast toast-{{ $type }}" role="{{ $type === 'error' ? 'alert' : 'status' }}" data-toast data-duration="{{ $duration }}">
            <span class="toast-icon" aria-hidden="true">{{ $glyphs[$type] }}</span>
            <div>
                <p class="toast-title">{{ $t['title'] ?? '' }}</p>
                @if (! empty($t['message']))<p class="toast-message">{{ $t['message'] }}</p>@endif
                @if (! empty($t['list']))
                    <ul class="toast-list">@foreach ($t['list'] as $line)<li>{{ $line }}</li>@endforeach</ul>
                @endif
                @if ($action && ! empty($action['url']) && ! empty($action['label']))
                    @if (strtoupper($action['method'] ?? 'GET') === 'GET')
                        <a class="toast-action" href="{{ $action['url'] }}">{{ $action['label'] }}</a>
                    @else
                        <form method="POST" action="{{ $action['url'] }}" style="margin:0">
                            @csrf
                            @method($action['method'])
                            <button type="submit" class="toast-action">{{ $action['label'] }}</button>
                        </form>
                    @endif
                @endif
            </div>
            <button type="button" class="toast-close" data-toast-close aria-label="Dismiss notification">×</button>
            @if ($duration > 0)<span class="toast-progress" style="animation-duration: {{ $duration }}ms"></span>@endif
        </div>
    @endforeach
</div>

<script>
    (() => {
        const stack = document.getElementById('toast-stack');
        if (!stack) return;

        const glyphs = { success: '✓', error: '✕', warning: '!', info: 'i' };

        const dismiss = (el) => {
            if (!el || el.dataset.leaving) return;
            el.dataset.leaving = '1';
            el.classList.add('toast-leave');
            setTimeout(() => el.remove(), 180);
        };

        // Auto-dismiss; pauses while the pointer is over the toast.
        const arm = (el) => {
            const duration = Number(el.dataset.duration || 0);
            if (!duration) return;
            let remaining = duration;
            let started = Date.now();
            let timer = setTimeout(() => dismiss(el), remaining);

            el.addEventListener('mouseenter', () => {
                clearTimeout(timer);
                remaining -= Date.now() - started;
                el.classList.add('toast-paused');
            });
            el.addEventListener('mouseleave', () => {
                started = Date.now();
                timer = setTimeout(() => dismiss(el), Math.max(remaining, 1500));
                el.classList.remove('toast-paused');
            });
        };

        stack.addEventListener('click', (event) => {
            const close = event.target.closest('[data-toast-close]');
            if (close) dismiss(close.closest('[data-toast]'));
        });

        stack.querySelectorAll('[data-toast]').forEach(arm);

        // For JavaScript-driven messages, e.g. after a failed fetch():
        //   showToast({ type: 'error', title: 'Message not sent', message: 'Check your connection.' })
        window.showToast = ({ type = 'info', title = '', message = '', duration } = {}) => {
            const kind = glyphs[type] ? type : 'info';
            const ms = duration ?? (kind === 'error' ? 0 : 5000);

            const el = document.createElement('div');
            el.className = `toast toast-${kind}`;
            el.setAttribute('role', kind === 'error' ? 'alert' : 'status');
            el.dataset.toast = '';
            el.dataset.duration = String(ms);

            const icon = document.createElement('span');
            icon.className = 'toast-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.textContent = glyphs[kind];

            const body = document.createElement('div');
            const heading = document.createElement('p');
            heading.className = 'toast-title';
            heading.textContent = title;
            body.append(heading);
            if (message) {
                const text = document.createElement('p');
                text.className = 'toast-message';
                text.textContent = message;
                body.append(text);
            }

            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'toast-close';
            close.setAttribute('aria-label', 'Dismiss notification');
            close.dataset.toastClose = '';
            close.textContent = '×';

            el.append(icon, body, close);
            if (ms > 0) {
                const bar = document.createElement('span');
                bar.className = 'toast-progress';
                bar.style.animationDuration = `${ms}ms`;
                el.append(bar);
            }

            stack.append(el);
            arm(el);
            return el;
        };
    })();
</script>