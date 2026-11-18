@extends('admin.partials.ops-theme')

@section('title', 'Incident queue · Kalibo MDRRMO')

@section('content')
        <header class="operations-page-head">
            <div><h1>Incident queue</h1><p>Review incoming reports, update status and priority, and follow up with reporters.</p></div>
        </header>
        <nav class="ops-summary-grid" aria-label="Incident queue summary filters">
            @foreach ([
                ['key' => '', 'label' => 'All reports', 'count' => $queueCounts['all'], 'badge' => 'Queue total', 'icon' => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 9h8M8 13h8M8 17h5"/>'],
                ['key' => 'submitted', 'label' => 'Submitted', 'count' => $queueCounts['submitted'], 'badge' => 'Awaiting triage', 'icon' => '<path d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14"/>'],
                ['key' => 'in_progress', 'label' => 'In progress', 'count' => $queueCounts['in_progress'], 'badge' => 'Active response', 'icon' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],
                ['key' => 'completed', 'label' => 'Completed', 'count' => $queueCounts['completed'], 'badge' => 'Resolved or closed', 'icon' => '<path d="m5 12 4 4L19 6"/><circle cx="12" cy="12" r="9"/>'],
            ] as $card)
                <a href="{{ route('operations.queue', $card['key'] === '' ? [] : ['status' => $card['key']]) }}" class="ops-summary-card" @if (($status ?? '') === $card['key']) aria-current="page" @endif>
                    <span class="ops-summary-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $card['icon'] !!}</svg></span>
                    <span class="ops-summary-copy"><span class="ops-summary-title">{{ $card['label'] }}</span><strong class="ops-summary-value">{{ number_format($card['count']) }}</strong><span class="ops-summary-badge">{{ $card['badge'] }}</span></span>
                </a>
            @endforeach
        </nav>
        <p id="queue-poll-status" class="mb-4 text-sm text-slate-500 dark:text-slate-400" aria-live="polite">Live queue updates are connecting.</p>
        <ul class="space-y-3">
        @forelse ($reports as $report)
            <li class="ops-panel flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Report reference</p><p class="mt-1 font-mono text-lg font-black text-slate-900 dark:text-white">{{ $report->reference_code }}</p>
                    @if ($report->photo_path)
                        <a href="{{ route('operations.report.photo', $report) }}" target="_blank" rel="noreferrer" class="mt-3 inline-flex items-center gap-3 text-sm font-bold text-blue-800 hover:underline dark:text-blue-300"><img src="{{ route('operations.report.photo', $report) }}" alt="Submitted incident photo" class="h-14 w-14 rounded-xl object-cover">View submitted photo</a>
                    @endif
                </div>
                <form method="POST" action="{{ route('operations.report.triage', $report) }}" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <select name="status" title="Set the current status for this incident." class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        <option value="submitted" {{ $report->status === 'submitted' ? 'selected' : '' }}>Submitted</option>
                        <option value="in_progress" {{ $report->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="resolved" {{ $report->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="closed" {{ $report->status === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                    <select name="priority" title="Set the response priority for this incident." class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        <option value="low" {{ $report->priority === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ $report->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ $report->priority === 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ $report->priority === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                    <button type="submit" class="ops-btn ops-btn-primary">Update</button>
                </form>
                <details class="w-full border-t border-slate-200 pt-4 dark:border-slate-700" data-staff-thread data-report-id="{{ $report->id }}" data-index-url="{{ route('operations.report.messages', $report) }}" data-store-url="{{ route('operations.report.messages.store', $report) }}">
                    <summary class="cursor-pointer text-sm font-bold text-blue-800 dark:text-blue-300">Reporter messages</summary>
                    <ol data-staff-message-list class="mt-3 max-h-56 space-y-2 overflow-y-auto" aria-live="polite"></ol>
                    <p data-staff-message-empty class="mt-2 text-sm text-slate-500 dark:text-slate-400">No messages yet.</p>
                    <p data-staff-message-alert class="mt-2 hidden rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200" role="status"></p>
                    <form data-staff-message-form class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end">
                        @csrf
                        <label class="flex-1 text-sm font-semibold text-slate-700 dark:text-slate-200">Reply to reporter
                            <textarea name="body" rows="2" maxlength="500" placeholder="Type a reply for the reporter" title="Write a message of up to 500 characters. Check Close thread to prevent more messages." required class="mt-1 w-full rounded-2xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none focus:border-blue-800 focus:ring-2 focus:ring-blue-800/15 dark:border-slate-600 dark:bg-slate-800 dark:text-white"></textarea>
                        </label>
                        <label class="flex items-center gap-2 pb-2 text-xs font-semibold text-slate-600 dark:text-slate-300"><input type="checkbox" name="close_thread" value="1" class="rounded border-slate-300 text-blue-800 focus:ring-blue-800"> Close thread</label>
                        <button type="submit" class="ops-btn ops-btn-primary">Send reply</button>
                    </form>
                </details>
            </li>
        @empty
            <li class="ops-panel p-8 text-center font-semibold text-slate-500 dark:text-slate-400">No reports pending.</li>
        @endforelse
        </ul>
@endsection
