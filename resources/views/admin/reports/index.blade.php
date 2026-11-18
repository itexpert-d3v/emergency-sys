@extends('admin.partials.ops-theme')

@section('title', 'System reporting · Kalibo MDRRMO')

@section('content')
        <header class="operations-page-head">
            <div><h1>System reporting</h1><p>Incident activity by reporting period.</p></div>
        </header>

        <section class="ops-card mb-5 p-4 sm:p-5" aria-label="Report filters and exports">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-3">
                <div><label for="period" class="mb-1 block text-xs font-bold">Period</label><select id="period" name="period" class="ops-input"><option value="daily" @selected($period['period'] === 'daily')>Daily</option><option value="weekly" @selected($period['period'] === 'weekly')>Weekly</option><option value="monthly" @selected($period['period'] === 'monthly')>Monthly</option></select></div>
                <div><label for="date" class="mb-1 block text-xs font-bold">Date in period</label><input id="date" type="date" name="date" value="{{ request('date', now()->toDateString()) }}" class="ops-input"></div>
                <button type="submit" class="ops-btn ops-btn-primary">Run report</button>
            </form>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4 dark:border-slate-700">
                <p class="ops-muted">{{ $period['start']->format('M j, Y') }} to {{ $period['end']->format('M j, Y') }} · {{ number_format($total) }} reports</p>
                <div class="flex flex-wrap gap-2">
                    @foreach (['pdf' => 'PDF', 'xlsx' => 'Excel (.xlsx)', 'docx' => 'Word (.docx)'] as $format => $label)
                        <a href="{{ route('admin.reports.export', ['format' => $format, 'period' => $period['period'], 'date' => request('date', now()->toDateString())]) }}" class="ops-btn ops-btn-sm" aria-label="Download report as {{ $label }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mb-5 flex flex-wrap gap-x-6 gap-y-2 border-y border-slate-200 py-3 text-sm dark:border-slate-700" aria-label="Status totals">
            @forelse ($statusCounts as $status => $count)
                <p><span class="text-slate-500 dark:text-slate-400">{{ ucfirst(str_replace('_', ' ', $status ?: 'Unknown')) }}</span> <strong class="ml-1 tabular-nums">{{ number_format($count) }}</strong></p>
            @empty
                <p class="text-slate-500 dark:text-slate-400">No reports in this period.</p>
            @endforelse
        </section>

        <section class="ops-card">
            <div class="ops-card-head"><h2 class="ops-panel-title">Incident records</h2><p class="ops-muted">Latest first</p></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[48rem] text-left text-sm">
                    <thead class="border-b border-slate-200 text-xs uppercase text-slate-500 dark:border-slate-700 dark:text-slate-400"><tr><th class="px-5 py-3">Reference</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Priority</th><th class="px-5 py-3">Submitted</th><th class="px-5 py-3">Location</th></tr></thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse ($reports as $report)
                        <tr><td class="px-5 py-4 font-mono">{{ $report->reference_code }}</td><td class="px-5 py-4">{{ $report->category?->nameFor(app()->getLocale(), 'en') ?? 'Incident report' }}</td><td class="px-5 py-4">{{ ucfirst(str_replace('_', ' ', $report->status)) }}</td><td class="px-5 py-4">{{ ucfirst($report->priority) }}</td><td class="px-5 py-4">{{ $report->submitted_at?->format('M j, Y g:i A') ?? '—' }}</td><td class="px-5 py-4">{{ $report->location_text ?: 'Not provided' }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">No incident reports for the selected period.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($reports->hasPages())<div class="ops-card-foot">{{ $reports->links() }}</div>@endif
        </section>
@endsection