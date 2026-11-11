<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="operations-surface min-h-screen bg-slate-50 text-slate-800 antialiased transition-colors duration-200 dark:bg-[#18181b] dark:text-slate-100" style="background-image:url('{{ asset('assets/lgu kalibo.jpg') }}');background-size:cover;background-position:center;background-attachment:fixed">
    <div class="app-photo-canvas min-h-screen">
    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <header class="civic-masthead mb-7 flex flex-col gap-4 rounded-[6px] p-6 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:bg-slate-900 dark:text-white">
            <div>@include('components.brand-marks')<p class="text-xs font-black uppercase tracking-[0.18em] text-blue-800 dark:text-blue-300">Kalibo MDRRMO · SOU · Staff operations</p><h1 class="mt-2 text-3xl font-black tracking-tight">Emergency reports queue</h1></div>
            <div class="flex items-center gap-3">
                @include('components.theme-toggle', ['placement' => 'inline'])
                <form method="POST" action="{{ route('staff.logout') }}">@csrf<button type="submit" class="rounded-full bg-blue-800 px-5 py-2.5 text-sm font-black text-white hover:bg-blue-900 dark:bg-blue-400 dark:text-slate-950 dark:hover:bg-blue-300">Logout</button></form>
            </div>
        </header>
        <p id="queue-poll-status" class="mb-4 text-sm text-slate-500 dark:text-slate-400" aria-live="polite">Live queue updates are connecting.</p>
        <ul class="space-y-3">
        @forelse ($reports as $report)
            <li class="civic-panel flex flex-col gap-4 rounded-[6px] p-5 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:bg-slate-900">
                <div><p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Report reference</p><p class="mt-1 font-mono text-lg font-black text-slate-900 dark:text-white">{{ $report->reference_code }}</p>
                    @if ($report->photo_path)
                        <a href="{{ route('staff.report.photo', $report) }}" target="_blank" rel="noreferrer" class="mt-3 inline-flex items-center gap-3 text-sm font-bold text-blue-800 hover:underline dark:text-blue-300"><img src="{{ route('staff.report.photo', $report) }}" alt="Submitted incident photo" class="h-14 w-14 rounded-xl object-cover">View submitted photo</a>
                    @endif
                </div>
                <form method="POST" action="{{ route('staff.report.triage', $report) }}" class="flex flex-wrap items-center gap-2">
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
                    <button type="submit" class="rounded-full bg-blue-800 px-4 py-2 text-sm font-black text-white hover:bg-blue-900 dark:bg-blue-400 dark:text-slate-950 dark:hover:bg-blue-300">Update</button>
                </form>
                <details class="w-full border-t border-slate-200 pt-4 dark:border-slate-700" data-staff-thread data-report-id="{{ $report->id }}" data-index-url="{{ route('staff.report.messages', $report) }}" data-store-url="{{ route('staff.report.messages.store', $report) }}">
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
                        <button type="submit" class="rounded-full bg-blue-800 px-4 py-2 text-sm font-bold text-white hover:bg-blue-900 dark:bg-blue-400 dark:text-slate-950 dark:hover:bg-blue-300">Send reply</button>
                    </form>
                </details>
            </li>
        @empty
            <li class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">No reports pending.</li>
        @endforelse
        </ul>
    </main>

    </div>
</body>
</html>
