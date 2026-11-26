@extends('layouts.public')

@section('title', __('Report tracking'))
@section('robots', 'noindex,nofollow')
@section('body-class', 'public-surface public-ambulance-surface min-h-screen antialiased')
@section('body-style')
    background-image: url('{{ asset('assets/ambulance.jpg') }}');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
@endsection

@push('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <main class="mx-auto max-w-3xl px-4 py-8">
        @php
            $trackingUrl = url('/track?reference=' . urlencode($reference_code) . '&token=' . urlencode($tracking_token ?? ''));
        @endphp

        {{-- data-reference and data-token are read by the status-refresh JS. Keep them. --}}
        <header class="civic-panel mb-6 mt-5 rounded-[6px] p-6 sm:p-8"
                data-status-widget
                data-reference="{{ $reference_code }}"
                data-token="{{ $tracking_token ?? '' }}"
                data-has-photo="{{ !empty($has_photo) ? 'true' : 'false' }}"
                data-thread-closed="{{ ($thread_closed ?? false) ? 'true' : 'false' }}">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-[var(--accent-text)]">{{ __('Report tracking') }}</p>
            <h1 class="mt-3 text-3xl font-black tracking-tight text-[var(--text)]">{{ __('Save these credentials') }}</h1>
            <p class="mt-2 text-sm text-[var(--text-muted)]">
                {{ __('The tracking link is the fastest way back. If you lose it, use the report number and private tracking code below at the tracking page.') }}
            </p>

            {{-- 1. Tracking link --}}
            <div class="mt-5">
                <label for="tracking-link" class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-[var(--text-muted)]">
                    {{ __('Tracking link') }}
                </label>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <input id="tracking-link" type="text" readonly value="{{ $trackingUrl }}"
                           class="w-full rounded-2xl border border-[var(--border)] bg-[var(--surface-card)] px-3 py-2 font-mono text-xs text-[var(--text)]"
                           onclick="this.select()">
                    <button type="button" data-copy-target="tracking-link"
                            class="rounded-full bg-[var(--accent)] px-4 py-2 text-sm font-bold text-[var(--on-accent)] hover:brightness-110">
                        {{ __('Copy link') }}
                    </button>
                </div>
            </div>

            {{-- 2 & 3. Reference + token, side by side --}}
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="tracking-reference" class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-[var(--text-muted)]">
                        {{ __('Report number') }}
                    </label>
                    <div class="flex gap-2">
                        <input id="tracking-reference" type="text" readonly value="{{ $reference_code }}"
                               class="w-full rounded-2xl border border-[var(--border)] bg-[var(--surface-card)] px-3 py-2 font-mono text-sm text-[var(--text)]"
                               onclick="this.select()">
                        <button type="button" data-copy-target="tracking-reference"
                                class="rounded-full border border-[var(--border)] bg-[var(--surface-card)] px-3 py-2 text-xs font-bold text-[var(--text)] hover:border-[var(--accent)] hover:text-[var(--accent-text)]">
                            {{ __('Copy') }}
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-[var(--text-muted)]">
                        {{ __('Public reference. Safe to share with staff.') }}
                    </p>
                </div>

                <div>
                    <label for="tracking-token" class="mb-2 block text-xs font-bold uppercase tracking-[0.18em] text-[var(--text-muted)]">
                        {{ __('Private tracking code') }}
                    </label>
                    <div class="flex gap-2">
                        <input id="tracking-token" type="text" readonly value="{{ $tracking_token ?? '' }}"
                               class="w-full rounded-2xl border border-[var(--border)] bg-[var(--surface-card)] px-3 py-2 font-mono text-sm text-[var(--text)]"
                               onclick="this.select()">
                        <button type="button" data-copy-target="tracking-token"
                                class="rounded-full border border-[var(--border)] bg-[var(--surface-card)] px-3 py-2 text-xs font-bold text-[var(--text)] hover:border-[var(--accent)] hover:text-[var(--accent-text)]">
                            {{ __('Copy') }}
                        </button>
                    </div>
                    <p class="mt-2 text-xs font-semibold text-[var(--danger)]">
                        {{ __('Keep this private. Anyone with this code can read your report.') }}
                    </p>
                </div>
            </div>

            <p class="mt-4 text-xs text-[var(--text-muted)]">
                {{ __('Lost the link?') }}
                <a href="{{ route('track') }}" class="font-bold text-[var(--accent-text)] underline underline-offset-2">
                    {{ __('Open the tracking page') }}
                </a>
                {{ __('and enter the report number and private tracking code above.') }}
            </p>

            <x-ui.badge tone="warning" id="report-status" class="mt-5" data-status-badge>
                {{ __(ucfirst(str_replace('_', ' ', $status ?? 'submitted'))) }}
            </x-ui.badge>

            <div data-photo-wrap class="mt-5 @if (empty($has_photo)) hidden @endif">
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-[var(--text-muted)]">{{ __('Attached photo') }}</p>
                <a href="{{ route('track.photo', $tracking_token) }}" target="_blank" rel="noreferrer" class="inline-flex">
                    <img src="{{ route('track.photo', $tracking_token) }}"
                         alt="{{ __('Photo submitted with this report') }}"
                         class="max-h-64 rounded-2xl object-contain shadow-sm">
                </a>
            </div>
        </header>

        <section class="grid gap-4 sm:grid-cols-2">
            <article class="rounded-3xl border border-[var(--border)] bg-[var(--surface-card)] p-5 shadow-sm">
                <h2 class="text-xs font-black uppercase tracking-[0.18em] text-[var(--text-muted)]">{{ __('Submitted') }}</h2>
                <p class="mt-3 font-bold text-[var(--text)]">{{ $submitted_at ? $submitted_at->format('F j, Y g:i A') : __('Unknown') }}</p>
            </article>
            <article class="rounded-3xl border border-[var(--border)] bg-[var(--surface-card)] p-5 shadow-sm">
                <h2 class="text-xs font-black uppercase tracking-[0.18em] text-[var(--text-muted)]">{{ __('Expiry countdown') }}</h2>
                <p class="mt-3 font-bold text-[var(--text)]">{{ $expires_at ? __('Expires on :date', ['date' => \Illuminate\Support\Carbon::parse($expires_at)->format('F j, Y g:i A')]) : __('No expiry set.') }}</p>
                @if ($expires_at)
                    <p data-expiry-countdown data-expires-at="{{ \Illuminate\Support\Carbon::parse($expires_at)->toISOString() }}" class="mt-2 text-sm font-semibold text-[var(--accent-text)]"></p>
                @endif
            </article>
        </section>

        <section class="mt-4 rounded-3xl border border-[var(--border)] bg-[var(--surface-card)] p-6 shadow-sm">
            <h2 class="text-xl font-black text-[var(--text)]">{{ __('Status timeline') }}</h2>
            <p id="status-refresh" class="mt-2 text-xs text-[var(--text-muted)]" aria-live="polite">{{ __('Status updates automatically.') }}</p>
            <ol data-status-timeline class="mt-5 space-y-4 border-l-2 border-[var(--border)] pl-5">
                @foreach ($timeline as $entry)
                    <li class="relative">
                        <span class="absolute -left-[1.65rem] top-1 h-3 w-3 rounded-full bg-[var(--accent)] ring-4 ring-[color-mix(in_srgb,var(--accent)_20%,transparent)]"></span>
                        <p class="font-bold text-[var(--text)]">{{ __($entry['label']) }}</p>
                        <p class="mt-1 text-sm text-[var(--text-muted)]">{{ $entry['timestamp'] ? \Illuminate\Support\Carbon::parse($entry['timestamp'])->format('F j, Y g:i A') : '' }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="mt-4 grid gap-4 sm:grid-cols-2">
            <article class="rounded-3xl border border-[var(--border)] bg-[var(--surface-card)] p-5 shadow-sm">
                <h2 class="font-black text-[var(--text)]">{{ __('Response updates') }}</h2>
                <ol data-public-updates class="mt-3 space-y-3 text-sm leading-6 text-[var(--text-muted)]">
                    @forelse ($public_updates as $update)
                        <li>
                            <p class="font-semibold text-[var(--text)]">{{ $update['body'] }}</p>
                            <p class="text-xs text-[var(--text-muted)]">{{ $update['created_at'] ? \Illuminate\Support\Carbon::parse($update['created_at'])->format('F j, Y g:i A') : '' }}</p>
                        </li>
                    @empty
                        <li data-public-updates-empty>{{ __('No response updates yet.') }}</li>
                    @endforelse
                </ol>
            </article>

            <article class="rounded-3xl border border-[var(--border)] bg-[var(--surface-card)] p-5 shadow-sm"
                     data-message-panel
                     data-token="{{ $tracking_token ?? '' }}"
                     data-closed="{{ ($thread_closed ?? false) ? 'true' : 'false' }}">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-black text-[var(--text)]">{{ __('Message thread') }}</h2>
                    <span class="text-[10px] font-bold uppercase tracking-[0.18em] text-[var(--text-muted)]">
                        {{ __('Staff & you') }}
                    </span>
                </div>

                @if (empty($tracking_token))
                    <p class="mt-3 rounded-xl bg-[var(--callout-bg)] px-3 py-2 text-sm text-[var(--callout-ink)]">
                        {{ __('Messaging is available only from your private tracking link. Open the link you saved after submitting the report to send a message.') }}
                    </p>
                @else
                    {{-- Thread area --}}
                    <div class="mt-4 rounded-2xl border border-[var(--border)] bg-[var(--surface-raised)] p-3">
                        <ol data-message-list
                            class="flex max-h-72 flex-col gap-2 overflow-y-auto pr-1"
                            aria-live="polite"></ol>
                        <p data-message-empty class="px-1 py-2 text-center text-sm text-[var(--text-muted)]">
                            {{ __('No messages yet. Start the conversation below.') }}
                        </p>
                    </div>

                    {{-- Alert (validation / errors / closed thread) --}}
                    <p data-message-alert
                       class="mt-3 hidden rounded-xl border border-[var(--danger)] bg-[color-mix(in_srgb,var(--danger)_12%,transparent)] px-3 py-2 text-sm font-semibold text-[var(--danger)]"
                       role="status"></p>

                    {{-- Composer --}}
                    <form data-message-form onsubmit="return false;" class="mt-4 space-y-3" novalidate>
                        <label for="reporter-message" class="block text-sm font-bold text-[var(--text)]">
                            {{ __('Send a message to staff') }}
                        </label>

                        <textarea
                            id="reporter-message"
                            name="body"
                            rows="3"
                            maxlength="500"
                            required
                            placeholder="{{ __('Type your message…') }}"
                            class="block w-full resize-none rounded-2xl border border-[var(--border)] bg-[var(--surface-card)] px-3.5 py-2.5 text-sm leading-6 text-[var(--text)] outline-none transition-colors focus:border-[var(--accent)] focus:ring-2 focus:ring-[color-mix(in_srgb,var(--accent)_35%,transparent)]"></textarea>

                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs text-[var(--text-muted)]">
                                {{ __('Up to 500 characters.') }}
                            </p>
                            <button
                                type="submit"
                                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full border border-[var(--accent-strong)] bg-[var(--accent)] px-5 py-2.5 text-sm font-black text-[var(--on-accent)] shadow-sm transition hover:brightness-110 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--accent)] focus-visible:ring-offset-2 focus-visible:ring-offset-[var(--surface-card)] disabled:cursor-not-allowed disabled:opacity-60">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 20l18-8L3 4l3 8-3 8z"/>
                                </svg>
                                <span>{{ __('Send message') }}</span>
                            </button>
                        </div>
                    </form>
                @endif
            </article>
        </section>

        <a href="{{ route('landing') }}" class="mt-6 inline-flex rounded-full border border-[var(--border)] bg-[var(--surface-card)] px-5 py-2.5 text-sm font-bold text-[var(--text)] hover:border-[var(--accent)] hover:text-[var(--accent-text)]">← {{ __('Back to home') }}</a>
    </main>
@endsection

@push('scripts')
    @vite('resources/js/messaging.js')

    <script>
        document.querySelectorAll('[data-copy-target]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const input = document.getElementById(btn.dataset.copyTarget);
                if (!input) return;

                try {
                    await navigator.clipboard.writeText(input.value);
                } catch {
                    input.select();
                    document.execCommand('copy');
                }

                const original = btn.textContent;
                btn.textContent = @json(__('Copied!'));
                window.showFeedback?.('success', @json(__('Copied to clipboard.')));

                setTimeout(() => {
                    btn.textContent = original;
                }, 1500);
            });
        });
    </script>
@endpush