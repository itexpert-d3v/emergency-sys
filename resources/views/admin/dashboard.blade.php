<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operations dashboard · Kalibo MDRRMO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="operations-surface admin-photo-surface min-h-screen text-slate-800 antialiased dark:text-slate-100">
    <div class="operations-shell">
        @include('admin.sidebar')
        <main class="operations-main">
            <header class="operations-page-head">
                <div><p class="operations-eyebrow">Admin panel · Operations overview</p><h1>Dashboard</h1><p>Incident activity and response status at a glance.</p></div>
                <div class="operations-head-actions">@include('components.theme-toggle', ['placement' => 'inline', 'iconOnly' => true])<a href="{{ route('admin.news.index') }}" class="operations-head-link">News &amp; advisories</a></div>
            </header>

            <section class="operations-stat-grid" aria-label="Incident report totals">
                <article class="operations-stat"><span>Total reports</span><strong>{{ number_format($summary['total']) }}</strong><small>All recorded reports</small></article>
                <article class="operations-stat"><span>Open reports</span><strong>{{ number_format($summary['open']) }}</strong><small>Submitted or in progress</small></article>
                <article class="operations-stat"><span>Resolved</span><strong>{{ number_format($summary['resolved']) }}</strong><small>Marked resolved</small></article>
                <article class="operations-stat operations-stat-critical"><span>Critical priority</span><strong>{{ number_format($summary['critical']) }}</strong><small>Requires close attention</small></article>
            </section>

            <section class="operations-graph-grid" aria-label="Incident statistics">
                <article class="operations-chart-panel">
                    <div class="operations-chart-heading"><div><h2>Reports submitted · last 7 days</h2><p>Count by submission date</p></div><strong>{{ number_format($reportTrend->sum('count')) }}<small> total</small></strong></div>
                    <div class="operations-bar-chart" role="img" aria-label="Daily submitted incident reports over the last seven days">
                        @foreach ($reportTrend as $day)
                            <div class="operations-bar-column" title="{{ \Illuminate\Support\Carbon::parse($day['date'])->format('F j') }}: {{ $day['count'] }} reports">
                                <span class="operations-bar-value">{{ $day['count'] }}</span>
                                <div class="operations-bar-track"><span class="operations-bar" style="height: {{ $day['height'] }}%"></span></div>
                                <span class="operations-bar-label">{{ $day['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="operations-chart-panel">
                    <div class="operations-chart-heading"><div><h2>Report status breakdown</h2><p>Current records by workflow state</p></div></div>
                    @php($statusTotal = max(1, (int) $statusCounts->sum()))
                    <div class="operations-status-chart">
                        @foreach (['submitted' => 'Submitted', 'in_progress' => 'In progress', 'queued' => 'Queued', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $key => $label)
                            @php($count = (int) ($statusCounts[$key] ?? 0))
                            <div class="operations-status-row"><div class="operations-status-label"><span>{{ $label }}</span><strong>{{ $count }}</strong></div><div class="operations-status-track" role="img" aria-label="{{ $label }}: {{ $count }} reports"><span style="width: {{ round($count / $statusTotal * 100) }}%"></span></div></div>
                        @endforeach
                    </div>
                </article>
            </section>

            <section class="operations-table-panel" aria-labelledby="recent-reports-heading">
                <div class="operations-table-heading"><div><h2 id="recent-reports-heading">Recent reports</h2><p>Latest 12 incident records</p></div><a href="{{ route('admin.dashboard.queue') }}">Open incident queue <span aria-hidden="true">→</span></a></div>
                <div class="overflow-x-auto"><table class="operations-table"><thead><tr><th>Incident</th><th>Location</th><th>Status</th><th>Priority</th><th>Received</th></tr></thead><tbody>
                    @forelse ($reports as $report)
                        <tr><td><strong>{{ $report->category?->nameFor(app()->getLocale(), 'en') ?? 'Incident' }}</strong><small>{{ $report->reference_code }}</small></td><td>{{ $report->location_text ?: 'Not provided' }}</td><td><span class="operations-state operations-state-{{ $report->status }}">{{ ucfirst(str_replace('_', ' ', $report->status ?? 'open')) }}</span></td><td><span class="operations-priority operations-priority-{{ $report->priority }}">{{ ucfirst($report->priority) }}</span></td><td>{{ $report->submitted_at?->format('M j, Y H:i') ?? '—' }}</td></tr>
                    @empty
+                        <tr><td colspan="5" class="operations-empty-row">No incident reports recorded yet.</td></tr>
+                    @endforelse
+                </tbody></table></div>
+            </section>
+        </main>
+    </div>
+</body>
+</html>
