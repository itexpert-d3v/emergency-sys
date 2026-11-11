<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Incident Queue</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .queue-row > summary { list-style: none; }
        .queue-row > summary::-webkit-details-marker { display: none; }
        .queue-row[open] .queue-chevron { transform: rotate(180deg); }
        .queue-row[open] > summary { background: rgb(241 245 249 / 0.7); }
        .dark .queue-row[open] > summary { background: rgb(30 41 59 / 0.6); }
    </style>
</head>
<body class="operations-surface min-h-screen bg-slate-50 text-slate-800 antialiased dark:bg-[#18181b] dark:text-slate-100" style="background-image:url('{{ asset('assets/lgu kalibo.jpg') }}');background-size:cover;background-position:center;background-attachment:fixed">
    <div class="operations-shell">
        @include('admin.sidebar')
        <main class="operations-main">
            <header class="operations-page-head"><div><p class="operations-eyebrow">Operations</p><h1>Incident queue</h1><p>Click a row to update status and priority, dispatch, or message the reporter.</p></div><div class="operations-head-actions">@include('components.theme-toggle', ['placement' => 'inline', 'iconOnly' => true])</div></header>

            @if (session('status'))
                <p class="mb-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">{{ session('status') }}</p>
            @endif
            @if ($errors->any())
                <div class="mb-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm text-rose-900 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-200" role="alert">
                    @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif

            @php
                $statusStyles = [
                    'submitted'   => 'bg-sky-100 text-sky-900 dark:bg-sky-500/15 dark:text-sky-300',
                    'in_progress' => 'bg-amber-100 text-amber-900 dark:bg-amber-500/15 dark:text-amber-300',
                    'resolved'    => 'bg-emerald-100 text-emerald-900 dark:bg-emerald-500/15 dark:text-emerald-300',
                    'closed'      => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
                ];
                $priorityBorder = [
                    'critical' => 'border-red-600',
                    'high'     => 'border-orange-500',
                    'medium'   => 'border-amber-400',
                    'low'      => 'border-slate-300 dark:border-slate-600',
                ];
                $priorityText = [
                    'critical' => 'text-red-700 dark:text-red-400',
                    'high'     => 'text-orange-700 dark:text-orange-400',
                    'medium'   => 'text-amber-700 dark:text-amber-400',
                    'low'      => 'text-slate-500 dark:text-slate-400',
                ];
                $statusOptions = ['submitted' => 'Submitted', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'];
                $gridCols = 'lg:grid-cols-[9.5rem_minmax(0,1fr)_11rem_8rem_9rem]';
            @endphp

            <section class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                {{-- Toolbar --}}
                <div class="flex flex-col gap-3 border-b border-slate-200 p-3 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between sm:p-4">
                    <div class="flex items-baseline gap-2">
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">Reports</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400" id="queue-count">
                            @if (method_exists($reports, 'total'))
                                {{ $reports->total() }} total · showing {{ $reports->count() }}
                            @else
                                {{ count($reports) }} shown
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <label for="queue-search" class="sr-only">Search this page</label>
                        <input id="queue-search" type="search" placeholder="Search this page…" title="Filters the rows currently shown by reference, type, location or reporter" class="w-44 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800">
                        <form method="GET" action="{{ route('admin.dashboard.queue') }}" class="flex items-center gap-2">
                            <label for="status-filter" class="sr-only">Filter by status</label>
                            <select id="status-filter" name="status" title="Filter reports by status" class="rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800">
                                <option value="">All statuses</option>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="rounded-full bg-blue-800 px-4 py-1.5 text-sm font-bold text-white hover:bg-blue-900 dark:bg-blue-400 dark:text-slate-950">Filter</button>
                        </form>
                    </div>
                </div>

                {{-- Column headings (desktop) --}}
                <div class="hidden gap-x-3 border-b border-slate-200 bg-slate-50 px-4 py-2 text-[10px] font-black uppercase tracking-[0.16em] text-slate-500 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-400 lg:grid {{ $gridCols }}">
                    <span>Reference</span><span>Incident</span><span>Reporter</span><span>Submitted</span><span class="text-right">Status</span>
                </div>

                <div class="divide-y divide-slate-200 dark:divide-slate-700" id="queue-list">
                    @forelse ($reports as $report)
                        @php
                            $categoryName = $report->category?->nameFor(app()->getLocale(), 'en') ?? 'Incident report';
                            $searchText = \Illuminate\Support\Str::lower(implode(' ', [
                                $report->reference_code, $categoryName, $report->location_text,
                                $report->reporter_name, $report->contact_number, $report->status, $report->priority,
                            ]));
                        @endphp
                        <article data-report-row data-search="{{ $searchText }}" class="border-l-4 {{ $priorityBorder[$report->priority] ?? 'border-slate-300' }}">
                            <details class="queue-row" data-admin-thread data-index-url="{{ route('staff.report.messages', $report) }}" data-store-url="{{ route('staff.report.messages.store', $report) }}" data-closed="{{ $report->thread_closed ? 'true' : 'false' }}">
                                <summary class="grid cursor-pointer grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 gap-y-0.5 px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/50 sm:px-4 {{ $gridCols }}">
                                    <div class="min-w-0">
                                        <p class="font-mono text-sm font-black leading-tight text-slate-900 dark:text-white">{{ $report->reference_code }}</p>
                                        <p class="text-[11px] font-bold uppercase tracking-wide {{ $priorityText[$report->priority] ?? '' }}">{{ ucfirst($report->priority) }}</p>
                                    </div>

                                    <div class="order-3 col-span-2 min-w-0 lg:order-none lg:col-span-1">
                                        <p class="truncate text-sm font-bold text-slate-800 dark:text-slate-100">{{ $categoryName }}</p>
                                        <p class="truncate text-xs text-slate-500 dark:text-slate-400" title="{{ $report->location_text }}">{{ $report->location_text }}</p>
                                    </div>

                                    <div class="hidden min-w-0 lg:block">
                                        <p class="truncate text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $report->reporter_name }}</p>
                                        <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $report->contact_number }}</p>
                                    </div>

                                    <div class="hidden text-xs text-slate-600 dark:text-slate-300 lg:block" title="{{ $report->submitted_at?->format('M j, Y g:i A') }}">
                                        <p class="font-semibold">{{ $report->submitted_at?->format('M j, g:i A') ?? '—' }}</p>
                                        <p class="text-slate-400 dark:text-slate-500">{{ $report->submitted_at?->diffForHumans(null, true, true) }}</p>
                                    </div>

                                    <div class="order-2 flex items-center justify-end gap-2 lg:order-none">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold {{ $statusStyles[$report->status] ?? 'bg-slate-100 text-slate-700' }}">{{ ucfirst(str_replace('_', ' ', $report->status)) }}</span>
                                        <svg class="queue-chevron h-4 w-4 shrink-0 text-slate-400 transition-transform" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                                    </div>
                                </summary>

                                <div class="space-y-4 border-t border-slate-200 bg-slate-50/60 px-4 py-4 dark:border-slate-700 dark:bg-slate-800/30">
                                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
                                        {{-- Details --}}
                                        <div class="min-w-0 space-y-2 text-sm">
                                            <p class="whitespace-pre-wrap leading-6 text-slate-700 dark:text-slate-200">{{ $report->description }}</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400"><span class="font-bold">Location:</span> {{ $report->location_text }}@if($report->latitude && $report->longitude) · <a class="font-semibold text-blue-800 hover:underline dark:text-blue-300" target="_blank" rel="noreferrer" href="https://www.google.com/maps?q={{ $report->latitude }},{{ $report->longitude }}">Open map</a>@endif</p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400"><span class="font-bold">Reporter:</span> {{ $report->reporter_name }} · <a class="font-semibold text-blue-800 hover:underline dark:text-blue-300" href="tel:{{ $report->contact_number }}">{{ $report->contact_number }}</a> · Submitted {{ $report->submitted_at?->format('M j, Y g:i A') ?? '—' }}</p>
                                            @if ($report->reporting_for_other)
                                                <p class="text-xs text-slate-500 dark:text-slate-400"><span class="font-bold">Reporting for someone else:</span> {{ $report->relationship ?: 'relationship not given' }}@if(!is_null($report->victim_contactable)) · person {{ $report->victim_contactable ? 'can' : 'cannot' }} be contacted directly @endif</p>
                                            @endif
                                            @if ($report->photo_path)
                                                <a href="{{ route('staff.report.photo', $report) }}" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 text-sm font-bold text-blue-800 hover:underline dark:text-blue-300">View submitted photo</a>
                                            @endif
                                        </div>

                                        {{-- Actions --}}
                                        <div class="space-y-3">
                                            <form method="POST" action="{{ route('staff.report.triage', $report) }}" class="flex flex-wrap items-center gap-2 rounded-xl bg-white p-2.5 dark:bg-slate-900">
                                                @csrf
                                                <input type="hidden" name="return_to" value="admin">
                                                <label class="sr-only" for="status-{{ $report->id }}">Report status</label>
                                                <select id="status-{{ $report->id }}" name="status" title="Update the report status" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900">
                                                    @foreach ($statusOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected($report->status === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <label class="sr-only" for="priority-{{ $report->id }}">Response priority</label>
                                                <select id="priority-{{ $report->id }}" name="priority" title="Update the response priority" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-900">
                                                    @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $value => $label)
                                                        <option value="{{ $value }}" @selected($report->priority === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="rounded-full bg-blue-800 px-3.5 py-1.5 text-sm font-bold text-white hover:bg-blue-900 dark:bg-blue-400 dark:text-slate-950">Save</button>
                                            </form>

                                            @if (!in_array($report->status, ['resolved', 'closed'], true))
                                                <form method="POST" action="{{ route('admin.report.dispatch', $report) }}" class="space-y-2 rounded-xl border border-slate-200 bg-white p-2.5 dark:border-slate-700 dark:bg-slate-900">
                                                    @csrf
                                                    <fieldset><legend class="text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">Confirmed response dispatch</legend>
                                                        <div class="mt-1.5 flex flex-wrap gap-3 text-sm text-slate-700 dark:text-slate-200"><label class="flex items-center gap-1.5"><input type="radio" name="responder" value="ambulance" required> Ambulance</label><label class="flex items-center gap-1.5"><input type="radio" name="responder" value="response_group" required> Response group</label></div>
                                                    </fieldset>
                                                    <label class="flex items-start gap-2 text-xs leading-5 text-slate-600 dark:text-slate-300"><input type="checkbox" name="dispatch_confirmed" value="1" required class="mt-0.5"> I confirm this unit has been dispatched and is on the way.</label>
                                                    <button type="submit" class="rounded-full bg-blue-800 px-3.5 py-1.5 text-sm font-bold text-white hover:bg-blue-900 dark:bg-blue-400 dark:text-slate-950">Notify reporter</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Messages: loads only while this row is open (see messaging.js) --}}
                                    <div data-staff-message-panel data-report-id="{{ $report->id }}" class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                                        <p class="text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">Message reporter</p>
                                        <ol data-message-list class="mt-2 max-h-48 space-y-2 overflow-y-auto" aria-live="polite"></ol>
                                        <p data-message-empty class="mt-2 text-sm text-slate-500 dark:text-slate-400">No messages yet.</p>
                                        <p data-message-alert class="mt-2 hidden rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-500/10 dark:text-amber-200" role="status"></p>
                                        <form data-message-form method="post" action="#" onsubmit="return false;" class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-end">
                                            @csrf
                                            <label class="flex-1 text-sm font-semibold text-slate-700 dark:text-slate-200"><span class="sr-only">Message</span>
                                                <textarea name="body" rows="2" maxlength="500" placeholder="Write an update or question for the reporter" title="Send a message of up to 500 characters. Closing the thread prevents further messages." required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none focus:border-blue-800 focus:ring-2 focus:ring-blue-800/15 dark:border-slate-600 dark:bg-slate-800 dark:text-white"></textarea>
                                            </label>
                                            <label class="flex items-center gap-2 pb-2 text-xs font-semibold text-slate-600 dark:text-slate-300"><input type="checkbox" name="close_thread" value="1" class="rounded border-slate-300 text-blue-800 focus:ring-blue-800"> Close thread</label>
                                            <button type="submit" class="rounded-full bg-blue-800 px-4 py-2 text-sm font-bold text-white hover:bg-blue-900 dark:bg-blue-400 dark:text-slate-950">Send</button>
                                        </form>
                                    </div>
                                </div>
                            </details>
                        </article>
                    @empty
                        <div class="p-8 text-center text-slate-500 dark:text-slate-400">No reports match this filter.</div>
                    @endforelse
                </div>

                <p id="queue-no-match" class="hidden p-6 text-center text-sm text-slate-500 dark:text-slate-400">No reports on this page match your search.</p>

                @if (method_exists($reports, 'hasPages') && $reports->hasPages())
                    <div class="border-t border-slate-200 p-3 dark:border-slate-700">{{ $reports->withQueryString()->links() }}</div>
                @endif
            </section>
        </main>
    </div>

    <script>
        // Quick filter for the rows on the current page (no reload).
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
    </script>
</body>
</html>