@extends('admin.partials.ops-theme')

@section('title', 'Incident queue · Admin')

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')

        @php
            $statusOptions = ['submitted' => 'Submitted', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'];
            $statusBadge = ['submitted' => 'b-sky', 'in_progress' => 'b-amber', 'resolved' => 'b-emerald', 'closed' => 'b-zinc'];
            $prio = fn ($p) => in_array($p, ['critical', 'high', 'medium', 'low'], true) ? $p : 'low';
            $cols = '8.5rem minmax(0,1fr) 10rem 6rem 8.5rem';
            $activeStatus = (string) ($status ?? '');
            $queueRoute = auth()->user()->role === 'admin' ? 'admin.dashboard.queue' : 'operations.queue';
        @endphp

        <header class="operations-page-head">
            <div>
                <p class="operations-eyebrow">Operations</p>
                <h1>Incident queue</h1>
                <p>Open a report to update its status, dispatch responders, or message the reporter.</p>
            </div>
            <div class="operations-head-actions"></div>
        </header>

        <nav class="ops-summary-grid" aria-label="Incident queue summary filters">
            @foreach ([
                ['key' => '', 'label' => 'All reports', 'count' => $queueCounts['all'], 'badge' => 'Queue total', 'icon' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 9h8M8 13h8M8 17h5"/>'],
                ['key' => 'submitted', 'label' => 'Submitted', 'count' => $queueCounts['submitted'], 'badge' => 'Awaiting triage', 'icon' => '<path d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14"/>'],
                ['key' => 'in_progress', 'label' => 'In progress', 'count' => $queueCounts['in_progress'], 'badge' => 'Active response', 'icon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
                ['key' => 'completed', 'label' => 'Completed', 'count' => $queueCounts['completed'], 'badge' => 'Resolved or closed', 'icon' => '<path d="m5 12 4 4L19 6"/><circle cx="12" cy="12" r="9"/>'],
            ] as $card)
                <a href="{{ route($queueRoute, $card['key'] === '' ? [] : ['status' => $card['key']]) }}" class="ops-summary-card" @if ($activeStatus === $card['key']) aria-current="page" @endif>
                    <span class="ops-summary-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $card['icon'] !!}</svg></span>
                    <span class="ops-summary-copy"><span class="ops-summary-title">{{ $card['label'] }}</span><strong class="ops-summary-value">{{ number_format($card['count']) }}</strong><span class="ops-summary-badge">{{ $card['badge'] }}</span></span>
                </a>
            @endforeach
        </nav>

        <section class="ops-card">
            <div class="ops-card-head">
                <div class="flex flex-wrap items-center gap-3">
                    <p class="ops-muted ops-num" id="queue-count">
                        @if (method_exists($reports, 'total'))
                            {{ $reports->total() }} total &middot; showing {{ $reports->count() }}
                        @else
                            {{ count($reports) }} shown
                        @endif
                    </p>
                    <label for="queue-search" class="sr-only">Search this page</label>
                    <input id="queue-search" type="search" placeholder="Search this page…" class="ops-input" style="width: 14rem">
                </div>
            </div>

            <div class="ops-cols-head" style="--cols: {{ $cols }}; padding-left: calc(1.25rem + 3px)">
                <span>Reference</span><span>Incident</span><span>Reporter</span><span>Submitted</span><span class="text-right">Status</span>
            </div>

            <div class="ops-list" id="queue-list">
                @forelse ($reports as $report)
                    @php
                        $categoryName = $report->category?->nameFor(app()->getLocale(), 'en') ?? 'Incident report';
                        $searchText = \Illuminate\Support\Str::lower(implode(' ', [
                            $report->reference_code, $categoryName, $report->location_text,
                            $report->reporter_name, $report->contact_number, $report->status, $report->priority,
                        ]));
                        $p = $prio($report->priority);
                    @endphp
                    <article data-report-row data-search="{{ $searchText }}" class="ops-qrow pr-{{ $p }}">
                        <details id="queue-item-{{ $report->id }}" class="ops-acc" data-admin-thread data-index-url="{{ route('operations.report.messages', $report) }}" data-store-url="{{ route('operations.report.messages.store', $report) }}" data-closed="{{ $report->thread_closed ? 'true' : 'false' }}">

                            <summary class="ops-grid ops-row" style="--cols: {{ $cols }}">
                                <div class="min-w-0">
                                    <p class="ops-mono">{{ $report->reference_code }}</p>
                                    <span class="ops-prio">{{ ucfirst($report->priority) }} priority</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="ops-title ops-trunc">{{ $categoryName }}</p>
                                    <p class="ops-muted ops-trunc" title="{{ $report->location_text }}">{{ $report->location_text ?: 'Location not provided' }}</p>
                                </div>
                                <div class="hidden min-w-0 lg:block">
                                    <p class="ops-text ops-trunc" style="font-weight: 600">{{ $report->reporter_name }}</p>
                                    <p class="ops-muted ops-trunc">{{ $report->contact_number }}</p>
                                </div>
                                <div class="hidden lg:block" title="{{ $report->submitted_at?->format('M j, Y g:i A') }}">
                                    <p class="ops-text ops-num">{{ $report->submitted_at?->format('M j, g:i A') ?? '—' }}</p>
                                    <p class="ops-muted">{{ $report->submitted_at?->diffForHumans(null, true, true) }} ago</p>
                                </div>
                                <div class="ops-end">
                                    <span class="ops-badge {{ $statusBadge[$report->status] ?? 'b-zinc' }}">{{ ucfirst(str_replace('_', ' ', $report->status)) }}</span>
                                    <svg class="ops-chev" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                                </div>
                            </summary>

                            <div class="ops-acc-body ops-detail">

                                {{-- LEFT COLUMN --}}
                                <div class="ops-col">
                                    <section class="ops-panel">
                                        <div class="ops-panel-head">
                                            <p class="ops-panel-title">What happened</p>
                                            <a href="tel:{{ $report->contact_number }}" class="ops-btn ops-btn-primary ops-btn-sm" title="Call {{ $report->reporter_name }} at {{ $report->contact_number }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.13.96.36 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0122 16.92z"/></svg>
                                                Call reporter
                                            </a>
                                        </div>

                                        <p class="ops-quote whitespace-pre-wrap mt-3">{{ $report->description }}</p>

                                        <dl class="ops-facts mt-4">
                                            <div>
                                                <dt>Location</dt>
                                                <dd>{{ $report->location_text ?: 'Not provided' }}</dd>
                                            </div>
                                            <div>
                                                <dt>Reporter</dt>
                                                <dd>
                                                    {{ $report->reporter_name }}<br>
                                                    <a href="tel:{{ $report->contact_number }}">{{ $report->contact_number }}</a>
                                                </dd>
                                            </div>
                                            <div>
                                                <dt>Submitted</dt>
                                                <dd class="ops-num">{{ $report->submitted_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                                            </div>
                                            @if ($report->reporting_for_other)
                                                <div>
                                                    <dt>On behalf of</dt>
                                                    <dd>
                                                        {{ $report->relationship ?: 'Relationship not given' }}
                                                        @if (!is_null($report->victim_contactable))
                                                            <br><span class="ops-muted">Person {{ $report->victim_contactable ? 'can' : 'cannot' }} be contacted directly</span>
                                                        @endif
                                                    </dd>
                                                </div>
                                            @endif
                                        </dl>

                                        @if (($report->latitude && $report->longitude) || $report->photo_path)
                                            <div class="mt-4 flex flex-wrap gap-2">
                                                @if ($report->latitude && $report->longitude)
                                                    <a href="{{ route('operations.live-map', ['report_id' => $report->id, 'lat' => $report->latitude, 'lng' => $report->longitude]) }}"
                                                       target="_blank"
                                                       class="ops-btn ops-btn-sm">
                                                        Open in Live Map
                                                    </a>
                                                @endif
                                                
                                                @if ($report->photo_path)
                                                    <button type="button" 
                                                            class="ops-btn ops-btn-sm" 
                                                            onclick="openPhotoModal('{{ route('operations.report.photo', $report) }}')">
                                                        View photo
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </section>

                                    {{-- 2. Update Report & Side Column --}}
                                    <div class="ops-forms-grid">
                                        
                                        {{-- Update Report --}}
                                        <form method="POST" action="{{ route('operations.report.triage', $report) }}" class="ops-panel ops-form">
                                            @csrf
                                            <input type="hidden" name="return_to" value="admin">
                                            <p class="ops-panel-title">Update report</p>

                                            <div class="ops-field">
                                                <span class="ops-label">Status</span>
                                                <input type="hidden" name="status" value="{{ $report->status }}">
                                                <div class="ops-chips" style="--cols: 2">
                                                    @foreach ($statusOptions as $value => $label)
                                                        <label class="ops-chip">
                                                            <input type="radio" name="status" value="{{ $value }}" @checked($report->status === $value)>
                                                            <span>{{ $label }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="ops-field">
                                                <span class="ops-label">Priority</span>
                                                <input type="hidden" name="priority" value="{{ $prio($report->priority) }}">
                                                <div class="ops-chips" style="--cols: 4">
                                                    @foreach (['low' => 'Low', 'medium' => 'Med', 'high' => 'High', 'critical' => 'Crit'] as $value => $label)
                                                        <label class="ops-chip pr-{{ $value }}" title="{{ ucfirst($value) }} priority">
                                                            <input type="radio" name="priority" value="{{ $value }}" @checked($prio($report->priority) === $value)>
                                                            <span>{{ $label }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="ops-field">
                                                <label class="ops-label" for="category-{{ $report->id }}">Incident type</label>
                                                <select id="category-{{ $report->id }}" name="category_id" class="ops-input" required>
                                                    <option value="">Select incident type</option>
                                                    @foreach ($categories as $category)
                                                        <option value="{{ $category->id }}" @selected((int) $report->category_id === $category->id)>{{ $category->nameFor(app()->getLocale(), 'en') }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="ops-field">
                                                <label class="ops-label" for="remarks-{{ $report->id }}">Internal remarks</label>
                                                <textarea id="remarks-{{ $report->id }}" name="remarks" rows="3" maxlength="2000" class="ops-input"
                                                          placeholder="Call notes (not visible to the reporter)">{{ $report->remarks }}</textarea>
                                            </div>

                                            <div class="ops-form-submit">
                                                <button type="submit" class="ops-btn ops-btn-primary ops-btn-block">Save changes</button>
                                                <button type="submit" formaction="{{ route('admin.report.accept', $report) }}" formmethod="POST" formnovalidate class="ops-btn ops-btn-block">Accept report</button>
                                            </div>
                                        </form>

                                        {{-- New Side Column (Dispatch + Moderation) --}}
                                        @php
                                            $normalizedPhone = \App\Models\BannedPhone::normalize($report->contact_number);
                                            $phoneBanId = $bannedPhoneIdsByNumber[$normalizedPhone] ?? null;
                                            $reportIp = $report->reporter_ip ? \App\Models\BannedIp::normalize($report->reporter_ip) : null;
                                            $reportIpIsBanned = $reportIp && in_array($reportIp, $bannedIpAddresses, true);
                                        @endphp

                                        <div class="ops-side">
                                            <div class="ops-side-inner">

                                                {{-- Dispatch (natural height) --}}
                                                @if (!in_array($report->status, ['resolved', 'closed'], true))
                                                    <form method="POST" action="{{ route('admin.report.dispatch', $report) }}" class="ops-panel ops-panel--dispatch ops-form">
                                                        @csrf
                                                        <div>
                                                            <p class="ops-panel-title">Dispatch response</p>
                                                            <p class="ops-muted">Sends an "on the way" update to the reporter.</p>
                                                        </div>

                                                        <fieldset class="ops-units">
                                                            <legend class="sr-only">Responder</legend>
                                                            <label class="ops-unit">
                                                                <input type="radio" name="responder" value="ambulance" required>
                                                                <span>
                                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>
                                                                    Ambulance
                                                                </span>
                                                            </label>
                                                            <label class="ops-unit">
                                                                <input type="radio" name="responder" value="response_group" required>
                                                                <span>
                                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 20v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M21 20v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                                                                    Response group
                                                                </span>
                                                            </label>
                                                        </fieldset>

                                                        <label class="ops-confirm">
                                                            <input type="checkbox" name="dispatch_confirmed" value="1" required>
                                                            I confirm this unit is en route.
                                                        </label>

                                                        <button type="submit" class="ops-btn ops-btn-block">Notify reporter</button>
                                                    </form>
                                                @endif

                                                {{-- Moderation --}}
                                                <section class="ops-panel ops-mod-card">
                                                    <div class="ops-mod-card-head">
                                                        <p class="ops-panel-title">Moderation</p>
                                                        <p class="ops-muted">Block the reporter, reject or delete this report.</p>
                                                    </div>

                                                    <div class="ops-mod-scroll">
                                                        <div class="ops-mod-section">
                                                            <p class="ops-label">Block future submissions</p>
                                                            <p class="ops-muted">Phone bans persist; IP bans expire after 24 hours.</p>
                                                            <div class="ops-actions">
                                                                @if ($phoneBanId)
                                                                    <form method="POST" action="{{ route('admin.phone-bans.destroy', $phoneBanId) }}">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <input type="hidden" name="report_id" value="{{ $report->id }}">
                                                                        <button type="submit" class="ops-btn ops-btn-sm">Unban phone {{ $report->contact_number }}</button>
                                                                    </form>
                                                                @else
                                                                    <form method="POST" action="{{ route('admin.phone-bans.store') }}">
                                                                        @csrf
                                                                        <input type="hidden" name="phone_number" value="{{ $report->contact_number }}">
                                                                        <input type="hidden" name="report_id" value="{{ $report->id }}">
                                                                        <button type="submit" class="ops-btn ops-btn-sm ops-btn-danger">Ban phone {{ $report->contact_number }}</button>
                                                                    </form>
                                                                @endif

                                                                @if ($reportIp)
                                                                    @if ($reportIpIsBanned)
                                                                        <form method="POST" action="{{ route('admin.report.ip-ban.destroy', $report) }}">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <input type="hidden" name="report_id" value="{{ $report->id }}">
                                                                            <button type="submit" class="ops-btn ops-btn-sm">Unban IP {{ $reportIp }}</button>
                                                                        </form>
                                                                    @else
                                                                        <form method="POST" action="{{ route('admin.ip-bans.store') }}">
                                                                            @csrf
                                                                            <input type="hidden" name="ip_address" value="{{ $reportIp }}">
                                                                            <input type="hidden" name="report_id" value="{{ $report->id }}">
                                                                            <button type="submit" class="ops-btn ops-btn-sm ops-btn-danger">Ban IP {{ $reportIp }}</button>
                                                                        </form>
                                                                    @endif
                                                                @else
                                                                    <span class="ops-muted">Submitter IP not recorded</span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <form method="POST" action="{{ route('admin.report.reject', $report) }}" class="ops-mod-section">
                                                            @csrf
                                                            <label class="ops-label" for="rejection-reason-{{ $report->id }}">Reject report</label>
                                                            <textarea id="rejection-reason-{{ $report->id }}" name="rejection_reason" rows="2" maxlength="500" required class="ops-input" placeholder="Why is this report rejected?"></textarea>
                                                            <button type="submit" class="ops-btn ops-btn-sm ops-btn-danger" onclick="return confirm('Reject this report and record it on the moderation page?');">Reject and record</button>
                                                        </form>

                                                        <form method="POST" action="{{ route('admin.report.soft-delete', $report) }}" class="ops-mod-section" onsubmit="return confirm('Move this report to Deleted reports? It can be restored later.');">
                                                            @csrf
                                                            <p class="ops-label">Delete report</p>
                                                            <div class="ops-actions">
                                                                <button type="submit" class="ops-btn ops-btn-sm ops-btn-danger">Soft delete report</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </section>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                {{-- RIGHT COLUMN: Chat --}}
                                <aside class="ops-chat-panel">
                                    <div class="ops-panel-head ops-chat-head">
                                        <p class="ops-panel-title" style="margin:0">Conversation with reporter</p>
                                        @if ($report->thread_closed)
                                            <span class="ops-badge b-zinc">Thread closed</span>
                                        @endif
                                    </div>

                                    <div class="ops-chat-body">
                                        <ol data-message-list class="ops-thread space-y-2" aria-live="polite"></ol>
                                        <p data-message-empty class="ops-muted py-4 text-center">No messages yet.</p>
                                        <p data-message-alert class="mt-2 hidden rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900" role="status"></p>
                                    </div>

                                    <div class="ops-chat-footer">
                                        <form data-message-form method="post" action="#" onsubmit="return false;">
                                            @csrf
                                            <label class="block">
                                                <span class="sr-only">Message</span>
                                                <textarea name="body" rows="2" maxlength="500" required class="ops-input"
                                                          placeholder="Write an update..."></textarea>
                                            </label>
                                            <div class="ops-chat-actions">
                                                <label class="flex items-center gap-2" style="font-size:.75rem; font-weight:600; color:var(--o-ink-2)">
                                                    <input type="checkbox" name="close_thread" value="1"> Close thread
                                                </label>
                                                <button type="submit" class="ops-btn ops-btn-primary ops-btn-sm">Send message</button>
                                            </div>
                                        </form>
                                    </div>
                                </aside>

                            </div>
                        </details>
                    </article>
                @empty
                    <p class="ops-empty">No reports match this filter.</p>
                @endforelse
            </div>

            <p id="queue-no-match" class="ops-empty hidden">No reports on this page match your search.</p>

            @if (method_exists($reports, 'hasPages') && $reports->hasPages())
                <div class="ops-card-foot">{{ $reports->withQueryString()->links() }}</div>
            @endif
        </section>
{{-- PHOTO MODAL --}}
<div id="photo-modal" class="ops-modal-overlay hidden" onclick="closePhotoModal(event)">
    <div class="ops-modal-content">
        <button type="button" class="ops-modal-close" onclick="closePhotoModal(event)">&times;</button>
        <img id="photo-modal-img" src="" alt="Incident Photo" class="ops-modal-img">
    </div>
</div>

<x-toast-stack />
@endsection

@section('scripts')
<script>
    // Quick filter
    (() => {
        const input = document.getElementById('queue-search');
        const rows = [...document.querySelectorAll('[data-report-row]')];
        const none = document.getElementById('queue-no-match');
        if (!input || !rows.length) return;
        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            let visible = 0;
            rows.forEach((row) => {
                const show = !q || row.dataset.search.includes(q);
                row.classList.toggle('hidden', !show);
                if (show) visible++;
            });
            none.classList.toggle('hidden', visible > 0);
        });
    })();

    // Expand logic
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const expandId = urlParams.get('expand');
        if (expandId) {
            const targetRow = document.getElementById('queue-item-' + expandId);
            if (targetRow) {
                targetRow.setAttribute('open', 'true');
                setTimeout(() => {
                    targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const summary = targetRow.querySelector('summary');
                    if (summary) {
                        summary.classList.add('bg-blue-50', 'dark:bg-blue-900/20');
                        setTimeout(() => summary.classList.remove('bg-blue-50', 'dark:bg-blue-900/20'), 2000);
                    }
                }, 100);
            }
        }
    });

    // --- PHOTO MODAL ---
    function openPhotoModal(imageUrl) {
        const modal = document.getElementById('photo-modal');
        const img = document.getElementById('photo-modal-img');
        img.src = imageUrl;
        modal.classList.remove('hidden');
        setTimeout(() => modal.classList.add('show'), 10);
    }

    function closePhotoModal(event) {
        if (event.target.id === 'photo-modal' || event.target.classList.contains('ops-modal-close')) {
            const modal = document.getElementById('photo-modal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.getElementById('photo-modal-img').src = '';
            }, 200);
        }
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closePhotoModal({ target: document.getElementById('photo-modal') });
        }
    });
</script>
@endsection