@extends('admin.partials.ops-theme')

@section('title', 'Operations dashboard · Kalibo MDRRMO')

@section('head')
    {{-- TODO before production/PWA: `npm i chart.js`, import it in resources/js/app.js, and delete this CDN tag. --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
@endsection

@section('styles')
    <style>
        /*
         * Dashboard LAYOUT only. Colours, cards, buttons and badges come from
         * resources/css/app.css (ops-card, ops-btn, ops-badge, --status-*, ...).
         * Do not define colour values here.
         */
        .dash-main { padding: clamp(1rem, 2.5vw, 2.25rem); font-variant-numeric: tabular-nums; }

        .dash-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--border); }
        .dash-title { display: flex; align-items: center; margin: 0; font-size: 2rem; font-weight: 800; line-height: 1.1; }
        .dash-sub { margin: .35rem 0 0; color: var(--text-muted); font-size: 14px; }

        .dash-overview, .dash-charts { display: grid; gap: 1rem; margin-bottom: 1rem; grid-template-columns: minmax(0, 1fr); }
        @media (min-width: 1024px) {
            .dash-overview { grid-template-columns: 300px minmax(0, 1fr); }
            .dash-charts { grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr); }
        }

        /* Resolved ring */
        .dash-dial-card { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem; padding: 1.5rem; text-align: center; }
        .dash-dial { position: relative; flex: none; width: 168px; height: 168px; }
        .dash-dial-ring { width: 100%; height: 100%; transform: rotate(-90deg); }
        .dash-dial-track, .dash-dial-progress { fill: none; stroke-width: 11; }
        .dash-dial-track { stroke: var(--surface-raised); }
        .dash-dial-progress { stroke: var(--status-resolved); stroke-linecap: round; transition: stroke-dasharray 600ms cubic-bezier(.22, 1, .36, 1); }
        .dash-dial-center { position: absolute; inset: 0; display: grid; place-items: center; }
        .dash-dial-value { font-size: 2.25rem; font-weight: 800; line-height: 1; }
        .dash-dial-value small { margin-left: 2px; color: var(--text-muted); font-size: 1rem; font-weight: 700; }
        .dash-dial-label { margin: 0; font-size: 1rem; font-weight: 700; }
        .dash-dial-note { margin: .25rem 0 0; color: var(--text-muted); font-size: 13px; }

        /* Stat tiles */
        .dash-stats { display: grid; gap: 1rem; grid-template-columns: minmax(0, 1fr); }
        @media (min-width: 560px) { .dash-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .dash-stat { display: flex; min-width: 0; align-items: center; gap: 1rem; padding: 1.1rem 1.25rem; }
        .dash-stat-icon { display: grid; flex: none; width: 44px; height: 44px; place-items: center; border: 1px solid var(--border); border-radius: 10px; background: var(--surface-raised); }
        .dash-stat-icon svg { width: 22px; height: 22px; }
        .dash-stat-label { margin: 0; font-size: 14px; font-weight: 600; }
        .dash-stat-value { margin: 2px 0 0; font-size: 2rem; font-weight: 800; line-height: 1.1; }
        .dash-stat-value.is-critical { color: var(--status-critical); }
        .dash-stat-note { margin: 2px 0 0; color: var(--text-muted); font-size: 13px; }

        .tone-brand { color: var(--accent-text); }
        .tone-amber { color: var(--status-in-progress); }
        .tone-red { color: var(--status-critical); }
        .tone-blue { color: var(--status-submitted); }
        .tone-muted { color: var(--text-muted); }

        /* Panels and charts */
        .dash-panel { min-width: 0; padding: 1.25rem; }
        .dash-panel-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.1rem; }
        .dash-panel-title { margin: 0; font-size: 1.05rem; font-weight: 800; }
        .dash-panel-sub { margin: 2px 0 0; color: var(--text-muted); font-size: 13px; }
        .dash-pill { flex: none; padding: .3rem .8rem; border: 1px solid var(--border); border-radius: 999px; background: var(--surface-raised); font-size: 13px; font-weight: 600; }
        .dash-chart-box { position: relative; height: 16rem; }
        .dash-chart-empty { position: absolute; inset: 0; display: grid; place-content: center; gap: .25rem; text-align: center; pointer-events: none; }
        .dash-chart-empty strong { font-size: 15px; }
        .dash-chart-empty span { color: var(--text-muted); font-size: 13px; }

        .dash-status { display: grid; grid-template-columns: minmax(150px, 190px) minmax(180px, 1fr); align-items: center; gap: 1.25rem; min-height: 16rem; }
        .dash-donut { position: relative; width: 190px; height: 190px; }
        .dash-donut-total { position: absolute; inset: 0; display: grid; place-content: center; text-align: center; pointer-events: none; }
        .dash-donut-total strong { font-size: 2rem; line-height: 1; }
        .dash-donut-total span { margin-top: 6px; color: var(--text-muted); font-size: 13px; }
        .dash-legend { display: grid; gap: 8px; margin: 0; padding: 0; list-style: none; }
        .dash-legend li { display: grid; grid-template-columns: 10px minmax(0, 1fr) auto 44px; align-items: center; gap: 8px; min-height: 40px; padding: 8px 10px; border: 1px solid var(--border); border-radius: var(--radius-control); font-size: 14px; }
        .dash-dot { width: 9px; height: 9px; border-radius: 50%; }
        .dot-submitted { background: var(--status-submitted); }
        .dot-in_progress { background: var(--status-in-progress); }
        .dot-resolved { background: var(--status-resolved); }
        .dot-closed { background: var(--status-closed); }
        .dash-pct { color: var(--text-muted); font-size: 13px; text-align: right; }
        @media (max-width: 639px) {
            .dash-status { grid-template-columns: minmax(0, 1fr); justify-items: center; }
            .dash-legend { width: 100%; }
            .dash-title { font-size: 1.75rem; }
        }

        /* Activity feed */
        .dash-feed-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.4rem 1.4rem 1rem; }
        .dash-feed { margin: 0; padding: 0 .5rem .75rem; list-style: none; }
        .dash-row { display: flex; flex-direction: column; gap: .9rem; padding: .9rem; border-bottom: 1px solid var(--border); border-radius: 10px; transition: background-color 150ms ease; }
        .dash-row:last-child { border-bottom: 0; }
        @media (min-width: 640px) { .dash-row { flex-direction: row; align-items: center; justify-content: space-between; } }
        .dash-row-main { display: flex; align-items: center; gap: 1rem; min-width: 0; }
        .dash-row-icon { display: grid; flex: none; width: 40px; height: 40px; place-items: center; border: 1px solid var(--border); border-radius: 10px; background: var(--surface-raised); }
        .dash-row-icon svg { width: 20px; height: 20px; }
        .dash-row-body { min-width: 0; }
        .dash-row-title { display: flex; flex-wrap: wrap; align-items: baseline; gap: .25rem .75rem; }
        .dash-row-cat { font-size: .95rem; font-weight: 700; }
        .dash-row-ref { color: var(--text-muted); font-size: 13px; font-weight: 600; }
        .dash-row-loc { display: flex; align-items: center; gap: .35rem; min-width: 0; margin: .2rem 0 0; color: var(--text-muted); font-size: 13px; }
        .dash-row-loc svg { width: 14px; height: 14px; flex: none; }
        .dash-row-loc span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dash-row-meta { display: flex; flex: none; align-items: center; gap: 1rem; }
        .dash-row-time { min-width: 7rem; color: var(--text-muted); font-size: 13px; font-weight: 600; text-align: right; }
        .dash-empty { padding: 2.5rem 1rem; color: var(--text-muted); font-size: 14px; text-align: center; }
    </style>
@endsection

@section('main-class', 'dash-main')

@section('content')

    @php
        $selectedMonth = $selectedMonth ?? null;

        $ringRate = $summary['total'] > 0
            ? max(0, min(100, (int) round(($summary['resolved'] / $summary['total']) * 100)))
            : 0;
        $inProgress = (int) data_get($statusCounts, 'in_progress', 0);
        $trendTotal = (int) collect($reportTrend)->sum('count');

        // Fixed order so the donut, the legend and the colours always line up.
        $statusRows = collect([
            'submitted' => 'Submitted',
            'in_progress' => 'In progress',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ])->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
            'count' => (int) data_get($statusCounts, $key, 0),
        ])->values();
        $statusTotal = (int) $statusRows->sum('count');
    @endphp

    <header class="dash-head">
        <div>
            <h1 class="dash-title">Command center</h1>
            <p class="dash-sub">Live incident activity and response metrics for Kalibo MDRRMO.</p>
        </div>
        <a href="{{ route('admin.news.index') }}" class="ops-btn">News &amp; advisories</a>
    </header>

    {{-- Overview --}}
    <section class="dash-overview" aria-label="Summary">
        <div class="ops-card dash-dial-card">
            <div class="dash-dial" role="img" aria-label="{{ $ringRate }} percent resolved: {{ number_format($summary['resolved']) }} of {{ number_format($summary['total']) }} reports">
                <svg class="dash-dial-ring" viewBox="0 0 120 120" aria-hidden="true">
                    <circle class="dash-dial-track" cx="60" cy="60" r="50" pathLength="100"/>
                    <circle class="dash-dial-progress" cx="60" cy="60" r="50" pathLength="100" stroke-dasharray="{{ $ringRate }} 100" stroke-dashoffset="0"/>
                </svg>
                <div class="dash-dial-center" aria-hidden="true">
                    <span class="dash-dial-value">{{ $ringRate }}<small>%</small></span>
                </div>
            </div>
            <div>
                <p class="dash-dial-label">Resolved</p>
                <p class="dash-dial-note">{{ number_format($summary['resolved']) }} of {{ number_format($summary['total']) }} reports closed out</p>
            </div>
        </div>

        <div class="dash-stats">
            <div class="ops-card dash-stat">
                <div class="dash-stat-icon tone-brand" aria-hidden="true">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <p class="dash-stat-label">Total reports</p>
                    <p class="dash-stat-value">{{ number_format($summary['total']) }}</p>
                    <p class="dash-stat-note">All recorded</p>
                </div>
            </div>

            <div class="ops-card dash-stat">
                <div class="dash-stat-icon tone-amber" aria-hidden="true">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                </div>
                <div>
                    <p class="dash-stat-label">Open cases</p>
                    <p class="dash-stat-value">{{ number_format($summary['open']) }}</p>
                    <p class="dash-stat-note">Submitted or in progress</p>
                </div>
            </div>

            <div class="ops-card dash-stat">
                <div class="dash-stat-icon tone-red" aria-hidden="true">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <p class="dash-stat-label">Critical priority</p>
                    <p @class(['dash-stat-value', 'is-critical' => $summary['critical'] > 0])>{{ number_format($summary['critical']) }}</p>
                    <p class="dash-stat-note">Needs attention</p>
                </div>
            </div>

            <div class="ops-card dash-stat">
                <div class="dash-stat-icon tone-blue" aria-hidden="true">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="dash-stat-label">In progress</p>
                    <p class="dash-stat-value">{{ number_format($inProgress) }}</p>
                    <p class="dash-stat-note">Being handled</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Charts --}}
    <section class="dash-charts" aria-label="Charts">
        <div class="ops-card dash-panel">
            <div class="dash-panel-head">
                <div>
                    <h2 class="dash-panel-title">Volume trend</h2>
                    <p class="dash-panel-sub">{{ $selectedMonth ? 'Reports submitted in selected month' : 'Reports submitted over the last 7 days' }}</p>
                </div>
                <span class="dash-pill">{{ number_format($trendTotal) }} {{ str('report')->plural($trendTotal) }} in {{ $selectedMonth ? 'selected month' : '7 days' }}</span>
            </div>

            <div class="dash-chart-box">
                <canvas id="trendChart" aria-label="Bar chart of reports per day" role="img"></canvas>
                @if ($trendTotal === 0)
                    <div class="dash-chart-empty" role="status">
                        <strong>No reports {{ $selectedMonth ? 'in the selected month' : 'in the last 7 days' }}</strong>
                        <span>New reports will appear here.</span>
                    </div>
                @endif
            </div>

            <table class="sr-only">
                <caption>Reports submitted {{ $selectedMonth ? 'in selected month' : 'over the last 7 days' }}</caption>
                <thead><tr><th>Date</th><th>Reports</th></tr></thead>
                <tbody>
                    @foreach ($reportTrend as $trendDay)
                        <tr><td>{{ $trendDay['date'] }}</td><td>{{ $trendDay['count'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="ops-card dash-panel">
            <div class="dash-panel-head">
                <div>
                    <h2 class="dash-panel-title">Status breakdown</h2>
                    <p class="dash-panel-sub">Current records by state</p>
                </div>
            </div>

            <div class="dash-status">
                <div class="dash-donut">
                    <canvas id="statusChart" aria-label="Doughnut chart of reports by status" role="img"></canvas>
                    <div class="dash-donut-total" aria-hidden="true">
                        <strong>{{ number_format($statusTotal) }}</strong>
                        <span>{{ str('report')->plural($statusTotal) }}</span>
                    </div>
                </div>

                <ul class="dash-legend" aria-label="Reports by status">
                    @foreach ($statusRows as $row)
                        <li>
                            <span class="dash-dot dot-{{ $row['key'] }}" aria-hidden="true"></span>
                            <span>{{ $row['label'] }}</span>
                            <strong>{{ number_format($row['count']) }}</strong>
                            <span class="dash-pct">{{ $statusTotal > 0 ? round($row['count'] / $statusTotal * 100) : 0 }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- Recent activity --}}
    <section class="ops-card" aria-label="Recent activity">
        <div class="dash-feed-head">
            <div>
                <h2 class="dash-panel-title">Recent activity</h2>
                <p class="dash-panel-sub">Latest incident reports</p>
            </div>
            <a href="{{ route(auth()->user()->role === 'admin' ? 'admin.dashboard.queue' : 'operations.queue') }}" class="ops-btn ops-btn-primary">Open full queue</a>
        </div>

        <ul class="dash-feed">
            @forelse ($reports as $report)
                @php
                    $statusClass = match($report->status) {
                        'submitted' => 'b-sky', 'in_progress' => 'b-amber',
                        'resolved' => 'b-emerald', 'closed' => 'b-zinc', default => 'b-zinc',
                    };
                @endphp
                <li class="dash-row ops-row">
                    <div class="dash-row-main">
                        <div class="dash-row-icon" aria-hidden="true">
                            @if($report->priority === 'critical')
                                <svg class="tone-red" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            @elseif($report->priority === 'high')
                                <svg class="tone-amber" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            @else
                                <svg class="tone-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @endif
                        </div>

                        <div class="dash-row-body">
                            <div class="dash-row-title">
                                <span class="dash-row-cat">{{ $report->category?->nameFor(app()->getLocale(), 'en') ?? 'Incident report' }}</span>
                                <span class="dash-row-ref">{{ $report->reference_code }}</span>
                            </div>
                            <p class="dash-row-loc">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span>{{ $report->location_text ?: 'Location not provided' }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="dash-row-meta">
                        <span class="ops-badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $report->status ?? 'open')) }}</span>
                        <span class="dash-row-time">{{ $report->submitted_at?->format('M j, g:i A') ?? '—' }}</span>
                    </div>
                </li>
            @empty
                <li class="dash-empty">No incident reports yet. New reports appear here as soon as they're submitted.</li>
            @endforelse
        </ul> 
    </section>

@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // If the CDN is blocked, the legend and the screen-reader table still carry the data.
            if (typeof Chart === 'undefined') return;

            const trend = @json($reportTrend);
            const statusRows = @json($statusRows);
            const today = @json(now()->toDateString()); // server date, same timezone as the trend dates
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const css = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

            const dayLabel = (d) => {
                const dt = new Date(String(d.date) + 'T12:00:00');
                if (isNaN(dt)) return d.label ?? String(d.date);
                return new Intl.DateTimeFormat(undefined, { weekday: 'short' }).format(dt) + ' ' + dt.getDate();
            };

            const maxTrend = Math.max(0, ...trend.map((d) => Number(d.count) || 0));
            const scaleMax = Math.max(5, Math.ceil(maxTrend / 5) * 5);
            const step = scaleMax <= 5 ? 1 : Math.ceil(scaleMax / 5);

            let charts = [];

            // Rebuilt whenever the light/dark class changes, so chart colours always follow the tokens.
            function render() {
                charts.forEach((c) => c.destroy());
                charts = [];

                const c = {
                    text: css('--text-muted'),
                    grid: css('--border'),
                    surface: css('--surface-card'),
                    accent: css('--accent'),
                    submitted: css('--status-submitted'),
                    status: {
                        submitted: css('--status-submitted'),
                        in_progress: css('--status-in-progress'),
                        resolved: css('--status-resolved'),
                        closed: css('--status-closed'),
                    },
                };

                Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
                Chart.defaults.font.weight = 600;
                Chart.defaults.font.size = 13;

                const trendEl = document.getElementById('trendChart');
                if (trendEl) {
                    charts.push(new Chart(trendEl, {
                        type: 'bar',
                        data: {
                            labels: trend.map(dayLabel),
                            datasets: [{
                                label: 'Reports',
                                data: trend.map((d) => d.count),
                                backgroundColor: trend.map((d) => (d.date === today ? c.accent : c.submitted)),
                                borderRadius: 6,
                                borderSkipped: false,
                                maxBarThickness: 44,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: reduceMotion ? false : undefined,
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { label: (item) => item.formattedValue + (Number(item.raw) === 1 ? ' report' : ' reports') } },
                            },
                            scales: {
                                y: { beginAtZero: true, max: scaleMax, border: { display: false }, grid: { color: c.grid }, ticks: { color: c.text, stepSize: step, precision: 0 } },
                                x: { border: { display: false }, grid: { display: false }, ticks: { color: c.text, maxRotation: 0, autoSkip: true } },
                            },
                        },
                    }));
                }

                const statusEl = document.getElementById('statusChart');
                if (statusEl) {
                    const hasData = statusRows.some((r) => r.count > 0);
                    charts.push(new Chart(statusEl, {
                        type: 'doughnut',
                        data: {
                            labels: hasData ? statusRows.map((r) => r.label) : ['No reports'],
                            datasets: [{
                                data: hasData ? statusRows.map((r) => r.count) : [1],
                                backgroundColor: hasData ? statusRows.map((r) => c.status[r.key]) : [c.grid],
                                borderWidth: 3,
                                borderColor: c.surface,
                                borderRadius: 4,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: reduceMotion ? false : undefined,
                            cutout: '72%',
                            plugins: { legend: { display: false }, tooltip: { enabled: hasData } },
                        },
                    }));
                }
            }

            render();
            new MutationObserver(render).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        });
    </script>
@endsection