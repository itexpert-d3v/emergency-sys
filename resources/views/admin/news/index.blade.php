@extends('admin.partials.ops-theme')

@section('title', 'News dispatcher · Admin')

@section('styles')
    <style>
        /* Confirmation dialog. Lives outside .ops (top layer), so it uses literal Tailwind zinc/emerald values. */
        #confirm-dialog { margin: auto; padding: 0; border: 0; background: transparent; color: inherit; width: min(24rem, calc(100vw - 2rem)); max-width: none; overflow: visible; }
        #confirm-dialog::backdrop { background: rgba(24, 24, 27, .55); backdrop-filter: blur(3px); }
        #confirm-dialog[open] { animation: confirm-pop .15s ease-out; }
        @keyframes confirm-pop { from { opacity: 0; transform: translateY(6px) scale(.98); } to { opacity: 1; transform: none; } }
        #confirm-dialog .confirm-card { background: #fff; color: #18181b; border: 0; border-radius: 12px; padding: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .35); }
        #confirm-dialog .confirm-card h3 { margin: 0; font-size: 1rem; font-weight: 700; color: inherit; }
        #confirm-dialog .confirm-card p { margin: .5rem 0 0; font-size: .875rem; line-height: 1.5; color: #52525b; }
        #confirm-dialog .confirm-actions { margin-top: 1.25rem; display: flex; align-items: center; justify-content: flex-end; gap: .5rem; }
        #confirm-dialog .confirm-btn { appearance: none; cursor: pointer; border: 0; border-radius: 8px !important; padding: .5rem 1rem; min-height: 2.25rem; font-size: .8125rem; font-weight: 600; line-height: 1.25; }
        #confirm-dialog .confirm-btn.cancel { background: transparent; color: #52525b; }
        #confirm-dialog .confirm-btn.cancel:hover { background: #f4f4f5; color: #18181b; }
        #confirm-dialog .confirm-btn.archive { background: #18181b; color: #fff; }
        #confirm-dialog .confirm-btn.archive:hover { background: #27272a; }
        #confirm-dialog .confirm-btn.restore { background: #047857; color: #fff; }
        #confirm-dialog .confirm-btn.restore:hover { background: #065f46; }
        #confirm-dialog .confirm-btn:disabled { opacity: .6; cursor: wait; }
        html.dark #confirm-dialog .confirm-card { background: #202023; color: #fafafa; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .6), inset 0 0 0 1px rgba(255, 255, 255, .08); }
        html.dark #confirm-dialog .confirm-card p { color: #a1a1aa; }
        html.dark #confirm-dialog .confirm-btn.cancel { color: #d4d4d8; }
        html.dark #confirm-dialog .confirm-btn.cancel:hover { background: rgba(255, 255, 255, .08); color: #fff; }
        html.dark #confirm-dialog .confirm-btn.archive { background: #fafafa; color: #18181b; }
        html.dark #confirm-dialog .confirm-btn.archive:hover { background: #fff; }
    </style>
@endsection

@section('content')

        @php
            $statusBadge = ['published' => 'b-emerald', 'scheduled' => 'b-sky', 'draft' => 'b-zinc', 'archived' => 'b-rose'];
            $cols = 'minmax(0,1.1fr) minmax(0,2fr) 7.5rem 6.5rem 9.5rem 9.5rem';
            $activeStatus = (string) ($status ?? '');
        @endphp

        <header class="operations-page-head">
            <div>
                <p class="operations-eyebrow">Public information</p>
                <h1>News &amp; advisories</h1>
                <p>Draft, schedule, publish, and archive official updates.</p>
            </div>
            <div class="operations-head-actions">
                <a href="{{ route('news.index') }}" class="ops-btn">Public portal</a>
            </div>
        </header>

        <nav class="ops-summary-grid" aria-label="Advisory summary filters">
            @foreach ([
                ['key' => '', 'label' => 'All advisories', 'count' => $counts->sum(), 'badge' => 'Every status', 'icon' => '<path d="M5 4h12a2 2 0 0 1 2 2v14H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/><path d="M8 8h7M8 12h7M8 16h4"/>'],
                ['key' => 'draft', 'label' => 'Drafts', 'count' => $counts['draft'] ?? 0, 'badge' => 'Not published', 'icon' => '<path d="M4 20h4l11-11a2.1 2.1 0 0 0-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/>'],
                ['key' => 'published', 'label' => 'Published', 'count' => $counts['published'] ?? 0, 'badge' => 'Public notices', 'icon' => '<path d="M4 11v2h4l5 4V7l-5 4H4Z"/><path d="M17 9a5 5 0 0 1 0 6m2-8a8 8 0 0 1 0 10"/>'],
                ['key' => 'archived_scheduled', 'label' => 'Archived / scheduled', 'count' => ($counts['archived'] ?? 0) + ($counts['scheduled'] ?? 0), 'badge' => 'Hidden or upcoming', 'icon' => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h3m-3 3h7"/>'],
            ] as $card)
                <a href="{{ route('admin.news.index', $card['key'] === '' ? [] : ['status' => $card['key']]) }}" class="ops-summary-card" @if ($activeStatus === $card['key']) aria-current="page" @endif>
                    <span class="ops-summary-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $card['icon'] !!}</svg></span>
                    <span class="ops-summary-copy"><span class="ops-summary-title">{{ $card['label'] }}</span><strong class="ops-summary-value">{{ number_format($card['count']) }}</strong><span class="ops-summary-badge">{{ $card['badge'] }}</span></span>
                </a>
            @endforeach
        </nav>

        <section class="ops-card">
            <div class="ops-card-head">
                <p class="ops-muted">{{ $activeStatus === 'archived_scheduled' ? 'Archived and scheduled advisories' : ($activeStatus === '' ? 'All advisories' : ucfirst($activeStatus) . ' advisories') }}</p>
                <a href="{{ route('admin.news.create') }}" class="ops-btn ops-btn-primary">Create update</a>
            </div>

            <div class="ops-cols-head" style="--cols: {{ $cols }}">
                <span>Category</span><span>Notice</span><span>Status</span><span>Severity</span><span>Publication time</span><span class="text-right">Actions</span>
            </div>

            <div class="ops-list">
                @forelse ($posts as $post)
                    @php
                        $shownStatus = $post->status === 'scheduled' && $post->published_at?->isPast() ? 'published' : $post->status;
                        $categoryName = \App\Models\NewsPost::CATEGORIES[$post->category] ?? $post->category;
                    @endphp
                    <article class="ops-grid ops-row" style="--cols: {{ $cols }}">
                        <p class="ops-text hidden lg:block">{{ $categoryName }}</p>

                        <div class="min-w-0">
                            <a href="{{ route('admin.news.edit', $post) }}" class="ops-title">{{ $post->title }}</a>
                            @if ($post->is_banner)
                                <span class="ops-badge b-rose ml-2" style="font-size:.6875rem">Banner</span>
                            @endif
                            <p class="ops-muted mt-1 lg:hidden">{{ $categoryName }} &middot; {{ $post->published_at?->format('M j, Y') ?? '—' }}</p>
                        </div>

                        <div><span class="ops-badge {{ $statusBadge[$shownStatus] ?? 'b-zinc' }}" style="text-transform: capitalize">{{ $shownStatus }}</span></div>

                        <div class="hidden lg:block">
                            @if ($post->severity === 'critical')
                                <span class="ops-badge b-rose">Critical</span>
                            @elseif ($post->severity === 'urgent')
                                <span class="ops-badge b-amber">Urgent</span>
                            @else
                                <span class="ops-muted" style="text-transform: capitalize">{{ $post->severity }}</span>
                            @endif
                        </div>

                        <p class="ops-muted ops-num hidden lg:block">{{ $post->published_at?->format('M j, Y g:i A') ?? '—' }}</p>

                        <div class="ops-end mt-1 lg:mt-0">
                            <a href="{{ route('admin.news.edit', $post) }}" class="ops-btn ops-btn-sm">Edit</a>

                            @if ($post->status !== 'archived')
                                <button type="button" class="ops-btn ops-btn-sm ops-btn-danger"
                                        data-confirm="archive"
                                        data-action="{{ route('admin.news.status', $post) }}"
                                        data-post-title="{{ Str::limit($post->title, 60) }}">Archive</button>
                            @else
                                <button type="button" class="ops-btn ops-btn-sm ops-btn-good"
                                        data-confirm="restore"
                                        data-action="{{ route('admin.news.unarchive', $post) }}"
                                        data-post-title="{{ Str::limit($post->title, 60) }}">Restore</button>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="ops-empty">No news items in this view. Click <strong>Create update</strong> to draft or publish an approved update.</p>
                @endforelse
            </div>

            @if ($posts->hasPages())
                <div class="ops-card-foot">{{ $posts->links() }}</div>
            @endif
        </section>
{{-- Confirmation dialog --}}
<dialog id="confirm-dialog" aria-labelledby="confirm-title">
    <form method="POST" action="#" class="confirm-card">
        @csrf
        <input type="hidden" name="_method" value="PATCH">
        <input type="hidden" name="status" value="archived" disabled>

        <h3 id="confirm-title" data-confirm-title></h3>
        <p data-confirm-message></p>

        <div class="confirm-actions">
            <button type="button" data-confirm-cancel class="confirm-btn cancel">Cancel</button>
            <button type="submit" data-confirm-submit class="confirm-btn archive">Confirm</button>
        </div>
    </form>
</dialog>
<x-toast-stack />
@endsection

@section('scripts')
<script>
    (() => {
        const dialog = document.getElementById('confirm-dialog');
        if (!dialog) return;

        const form = dialog.querySelector('form');
        const title = dialog.querySelector('[data-confirm-title]');
        const message = dialog.querySelector('[data-confirm-message]');
        const submit = dialog.querySelector('[data-confirm-submit]');
        const statusInput = form.querySelector('input[name="status"]');

        const modes = {
            archive: {
                title: 'Archive this post?',
                message: (name) => `"${name}" will be removed from the public portal. You can restore it later from the Archive tab.`,
                label: 'Yes, Archive',
                button: 'archive',
                sendStatus: true,
            },
            restore: {
                title: 'Restore this post?',
                message: (name) => `"${name}" will come back as a draft. Review it, then publish when you are ready.`,
                label: 'Yes, Restore',
                button: 'restore',
                sendStatus: false,
            },
        };

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-confirm]');

            if (trigger) {
                const mode = modes[trigger.dataset.confirm];
                if (!mode) return;

                form.action = trigger.dataset.action;
                statusInput.disabled = !mode.sendStatus;
                title.textContent = mode.title;
                message.textContent = mode.message(trigger.dataset.postTitle || 'This post');
                submit.textContent = mode.label;
                submit.disabled = false;
                submit.classList.remove('archive', 'restore');
                submit.classList.add(mode.button);
                dialog.showModal();
                return;
            }

            if (event.target.closest('[data-confirm-cancel]') || event.target === dialog) {
                dialog.close();
            }
        });

        form.addEventListener('submit', () => {
            submit.disabled = true;
            submit.textContent = 'Working…';
        });

        window.addEventListener('pageshow', () => { submit.disabled = false; });
    })();
</script>
@endsection