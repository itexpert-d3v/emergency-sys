@extends('admin.partials.ops-theme')

@section('title', 'Operations dashboard · Kalibo MDRRMO')

@section('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
@endsection

@section('styles')
    <style>
        /* ------------------------------------------------------------------
           Soft-UI (neumorphism) tokens. One surface colour; depth comes only
           from a light and a dark shadow cast from opposite corners.
           ------------------------------------------------------------------ */
        :root {
            --nm-bg: #e4e9f0;
            --nm-hi: #ffffff;
            --nm-lo: #b9c2d1;
            --nm-ink: #1c2738;
            --nm-ink-2: #46546a;
            --nm-ink-3: #5a687d;
            --nm-accent: #2457d6;
            --nm-accent-ink: #ffffff;
            --nm-sky: #0b6fa8;
            --nm-amber: #9a5b00;
            --nm-green: #0a7a52;
            --nm-red: #b42323;
            --nm-grey: #5a687d;

            --nm-raised: 9px 9px 20px var(--nm-lo), -9px -9px 20px var(--nm-hi);
            --nm-raised-sm: 5px 5px 11px var(--nm-lo), -5px -5px 11px var(--nm-hi);
            --nm-raised-xs: 3px 3px 7px var(--nm-lo), -3px -3px 7px var(--nm-hi);
            --nm-inset: inset 6px 6px 12px var(--nm-lo), inset -6px -6px 12px var(--nm-hi);
            --nm-inset-sm: inset 3px 3px 7px var(--nm-lo), inset -3px -3px 7px var(--nm-hi);
        }
        :root.dark {
            --nm-bg: #1d2129;
            --nm-hi: #282d38;
            --nm-lo: #111418;
            --nm-ink: #e9edf4;
            --nm-ink-2: #b5bfce;
            --nm-ink-3: #98a3b5;
            --nm-accent: #7ea4ff;
            --nm-accent-ink: #0f1830;
            --nm-sky: #6cc0f0;
            --nm-amber: #f2b24c;
            --nm-green: #4fd1a0;
            --nm-red: #ff8a84;
            --nm-grey: #98a3b5;
        }

        body.nm-body {
            background-color: var(--nm-bg) !important;
            background-image: none !important;
            color: var(--nm-ink);
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
        }
        .nm-main {
            padding: clamp(1rem, 2.5vw, 2.25rem);
            color: var(--nm-ink);
            font-variant-numeric: tabular-nums;
        }

        /* Surfaces */
        .nm-raised { background: var(--nm-bg); border-radius: 28px; box-shadow: var(--nm-raised); }
        .nm-well   { background: var(--nm-bg); border-radius: 20px; box-shadow: var(--nm-inset); }

        /* Header */
        .nm-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 2rem; }
        .nm-title { font-size: clamp(1.6rem, 2.4vw, 2.1rem); font-weight: 800; letter-spacing: -0.02em; line-height: 1.1; }
        .nm-sub { margin-top: .35rem; font-size: .9rem; color: var(--nm-ink-3); max-width: 60ch; }
        .nm-actions { display: flex; align-items: center; gap: 1rem; }

        /* Buttons: raised at rest, pressed on :active */
        .nm-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            min-height: 44px; padding: 0 1.35rem; border: 0; border-radius: 999px; cursor: pointer;
            background: var(--nm-bg); color: var(--nm-ink); font: inherit; font-size: .875rem; font-weight: 700;
            text-decoration: none; box-shadow: var(--nm-raised-sm);
            transition: box-shadow .15s ease, color .15s ease;
        }
        .nm-btn:hover { color: var(--nm-accent); }
        .nm-btn:active { box-shadow: var(--nm-inset-sm); }
        .nm-btn-primary { background: var(--nm-accent); color: var(--nm-accent-ink); }
        .nm-btn-primary:hover { color: var(--nm-accent-ink); filter: brightness(1.06); }
        .nm-btn:focus-visible, .nm-row:focus-visible { outline: 3px solid var(--nm-accent); outline-offset: 3px; }

        /* Overview: dial + stat tiles */
        .nm-overview { display: grid; gap: 1.75rem; margin-bottom: 1.75rem; grid-template-columns: 1fr; }
        @media (min-width: 1024px) { .nm-overview { grid-template-columns: 340px minmax(0, 1fr); } }

        .nm-dial-card { padding: 1.75rem; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 1.25rem; }
        
        /* Chart.js Dial Container */
        .nm-dial-chart { position: relative; width: 200px; height: 200px; display: flex; justify-content: center; align-items: center; }
        .nm-dial-overlay { position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: center; align-items: center; pointer-events: none; }
        .nm-dial-value { font-size: 2.4rem; font-weight: 800; letter-spacing: -0.03em; line-height: 1; color: var(--nm-green); }
        .nm-dial-value small { font-size: 1rem; font-weight: 700; color: var(--nm-ink-3); margin-left: 2px; }
        
        .nm-dial-label { font-size: 1rem; font-weight: 700; margin-top: 1rem; }
        .nm-dial-note { margin-top: .25rem; font-size: .85rem; color: var(--nm-ink-3); }

        .nm-stats { display: grid; gap: 1.75rem; grid-template-columns: 1fr; }
        @media (min-width: 560px) { .nm-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

        .nm-stat { padding: 1.4rem 1.5rem; display: flex; align-items: center; gap: 1.1rem; }
        .nm-stat-icon { flex: none; width: 52px; height: 52px; border-radius: 50%; display: grid; place-items: center; box-shadow: var(--nm-inset-sm); }
        .nm-stat-icon svg { width: 22px; height: 22px; }
        .nm-stat-label { font-size: .85rem; font-weight: 600; color: var(--nm-ink-2); }
        .nm-stat-value { font-size: 2rem; font-weight: 800; letter-spacing: -0.03em; line-height: 1.1; }
        .nm-stat-note { font-size: .78rem; color: var(--nm-ink-3); margin-top: 2px; }
        .tone-ink   { color: var(--nm-accent); }
        .tone-amber { color: var(--nm-amber); }
        .tone-red   { color: var(--nm-red); }
        .tone-sky   { color: var(--nm-sky); }

        /* Charts */
        .nm-charts { display: grid; gap: 1.75rem; margin-bottom: 1.75rem; grid-template-columns: 1fr; }
        @media (min-width: 1024px) { .nm-charts { grid-template-columns: 1.35fr 1fr; } }
        .nm-panel { padding: 1.5rem; }
        .nm-panel-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; }
        .nm-panel-title { font-size: 1.05rem; font-weight: 800; letter-spacing: -0.01em; }
        .nm-panel-sub { margin-top: 2px; font-size: .82rem; color: var(--nm-ink-3); }
        .nm-pill { flex: none; padding: .35rem .85rem; border-radius: 999px; font-size: .78rem; font-weight: 700; color: var(--nm-ink-2); box-shadow: var(--nm-inset-sm); }
        .nm-chart-well { position: relative; height: 17rem; padding: 1rem; }
        .nm-chart-well canvas { position: relative; }

        /* Activity feed */
        .nm-feed-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.5rem 1.5rem 1rem; }
        .nm-feed { list-style: none; margin: 0; padding: 0 1rem 1.25rem; display: grid; gap: .6rem; }
        .nm-row {
            display: flex; flex-direction: column; gap: .9rem; padding: .9rem 1rem; border-radius: 20px;
            transition: box-shadow .15s ease;
        }
        .nm-row:hover { box-shadow: var(--nm-inset-sm); }
        @media (min-width: 640px) { .nm-row { flex-direction: row; align-items: center; justify-content: space-between; } }
        .nm-row-main { display: flex; align-items: center; gap: 1rem; min-width: 0; }
        .nm-row-icon { flex: none; width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; box-shadow: var(--nm-raised-xs); }
        .nm-row-icon svg { width: 20px; height: 20px; }
        .nm-row-title { display: flex; flex-wrap: wrap; align-items: baseline; gap: .25rem .75rem; }
        .nm-row-cat { font-size: .92rem; font-weight: 700; }
        .nm-row-ref { font-size: .75rem; font-weight: 600; color: var(--nm-ink-3); letter-spacing: .02em; }
        .nm-row-loc { display: flex; align-items: center; gap: .35rem; margin-top: .2rem; font-size: .8rem; color: var(--nm-ink-3); min-width: 0; }
        .nm-row-loc svg { width: 14px; height: 14px; flex: none; }
        .nm-row-loc span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .nm-row-meta { display: flex; align-items: center; gap: 1rem; flex: none; }
        .nm-row-time { min-width: 6.5rem; text-align: right; font-size: .8rem; font-weight: 600; color: var(--nm-ink-2); }

        /* Status badge: inset pill, with dot AND word so colour is never the only signal */
        .nm-badge { display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .85rem; border-radius: 999px; font-size: .78rem; font-weight: 700; box-shadow: var(--nm-inset-sm); white-space: nowrap; }
        .nm-badge::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
        .b-sky { color: var(--nm-sky); } .b-amber { color: var(--nm-amber); } .b-emerald { color: var(--nm-green); } .b-zinc { color: var(--nm-grey); }
        .nm-empty { padding: 3rem 1rem; text-align: center; color: var(--nm-ink-3); font-size: .9rem; }

        @media (prefers-reduced-motion: reduce) {
            .nm-btn, .nm-row { transition: none; }
        }
    </style>
@endsection

@section('body-class', 'nm-body')
@section('main-class', 'nm-main')

@section('content')

            @php
                $resolveRate = $summary['total'] > 0 ? round($summary['resolved'] / $summary['total'] * 100) : 0;
                $inProgress  = (int) data_get($statusCounts, 'in_progress', 0);
            @endphp

            <header class="nm-head">
                <div>
                    <h1 class="nm-title">Command center</h1>
                    <p class="nm-sub">Live incident activity and response metrics for Kalibo MDRRMO.</p>
                </div>
                <div class="nm-actions">
                    <a href="{{ route('admin.news.index') }}" class="nm-btn">News &amp; advisories</a>
                </div>
            </header>

            {{-- Overview: resolution dial + stat tiles --}}
            <section class="nm-overview" aria-label="Summary">
                <div class="nm-raised nm-dial-card">
                    
                    {{-- Chart.js Doughnut dial – always visible, no hover required --}}
                    <div class="nm-dial-chart">
                        <canvas id="dialChart" aria-label="{{ $resolveRate }} percent of reports resolved" role="img"></canvas>
                        <div class="nm-dial-overlay">
                            <span class="nm-dial-value">{{ $resolveRate }}<small>%</small></span>
                        </div>
                    </div>

                    <div>
                        <p class="nm-dial-label">Resolved</p>
                        <p class="nm-dial-note">{{ number_format($summary['resolved']) }} of {{ number_format($summary['total']) }} reports closed out</p>
                    </div>
                </div>

                <div class="nm-stats">
                    <div class="nm-raised nm-stat">
                        <div class="nm-stat-icon tone-ink" aria-hidden="true">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <p class="nm-stat-label">Total reports</p>
                            <p class="nm-stat-value">{{ number_format($summary['total']) }}</p>
                            <p class="nm-stat-note">All recorded</p>
                        </div>
                    </div>

                    <div class="nm-raised nm-stat">
                        <div class="nm-stat-icon tone-amber" aria-hidden="true">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                        </div>
                        <div>
                            <p class="nm-stat-label">Open cases</p>
                            <p class="nm-stat-value tone-amber">{{ number_format($summary['open']) }}</p>
                            <p class="nm-stat-note">Submitted or in progress</p>
                        </div>
                    </div>

                    <div class="nm-raised nm-stat">
                        <div class="nm-stat-icon tone-red" aria-hidden="true">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <p class="nm-stat-label">Critical priority</p>
                            <p class="nm-stat-value tone-red">{{ number_format($summary['critical']) }}</p>
                            <p class="nm-stat-note">Needs attention</p>
                        </div>
                    </div>

                    <div class="nm-raised nm-stat">
                        <div class="nm-stat-icon tone-sky" aria-hidden="true">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="nm-stat-label">In progress</p>
                            <p class="nm-stat-value tone-sky">{{ number_format($inProgress) }}</p>
                            <p class="nm-stat-note">Being handled</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Charts --}}
            <section class="nm-charts" aria-label="Charts">
                <div class="nm-raised nm-panel">
                    <div class="nm-panel-head">
                        <div>
                            <h2 class="nm-panel-title">Volume trend</h2>
                            <p class="nm-panel-sub">Reports submitted over 7 days</p>
                        </div>
                        <span class="nm-pill">{{ $reportTrend->sum('count') }} reports</span>
                    </div>
                    <div class="nm-well nm-chart-well">
                        <canvas id="trendChart" aria-label="Bar chart of reports per day for the last 7 days" role="img"></canvas>
                    </div>
                </div>

                <div class="nm-raised nm-panel">
                    <div class="nm-panel-head">
                        <div>
                            <h2 class="nm-panel-title">Status breakdown</h2>
                            <p class="nm-panel-sub">Current records by state</p>
                        </div>
                    </div>
                    <div class="nm-well nm-chart-well">
                        <canvas id="statusChart" aria-label="Doughnut chart of reports by status" role="img"></canvas>
                    </div>
                </div>
            </section>

            {{-- Recent activity --}}
            <section class="nm-raised" aria-label="Recent activity">
                <div class="nm-feed-head">
                    <div>
                        <h2 class="nm-panel-title">Recent activity</h2>
                        <p class="nm-panel-sub">Latest incident reports</p>
                    </div>
                    <a href="{{ route(auth()->user()->role === 'admin' ? 'admin.dashboard.queue' : 'operations.queue') }}" class="nm-btn nm-btn-primary">Open full queue</a>
                </div>

                <ul class="nm-feed">
                    @forelse ($reports as $report)
                        @php
                            $statusClass = match($report->status) {
                                'submitted' => 'b-sky', 'in_progress' => 'b-amber',
                                'resolved' => 'b-emerald', 'closed' => 'b-zinc', default => 'b-zinc',
                            };
                        @endphp
                        <li class="nm-row">
                            <div class="nm-row-main">
                                <div class="nm-row-icon" aria-hidden="true">
                                    @if($report->priority === 'critical')
                                        <svg class="tone-red" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    @elseif($report->priority === 'high')
                                        <svg class="tone-amber" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    @else
                                        <svg style="color:var(--nm-ink-3)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @endif
                                </div>

                                <div style="min-width:0">
                                    <div class="nm-row-title">
                                        <span class="nm-row-cat">{{ $report->category?->nameFor(app()->getLocale(), 'en') ?? 'Incident report' }}</span>
                                        <span class="nm-row-ref">{{ $report->reference_code }}</span>
                                    </div>
                                    <p class="nm-row-loc">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span>{{ $report->location_text ?: 'Location not provided' }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="nm-row-meta">
                                <span class="nm-badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $report->status ?? 'open')) }}</span>
                                <span class="nm-row-time">{{ $report->submitted_at?->format('M j, g:i A') ?? '—' }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="nm-empty">No incident reports yet. New reports appear here as soon as they're submitted.</li>
                    @endforelse
                </ul>
            </section>

@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const root = document.documentElement;
            const css = (name) => getComputedStyle(root).getPropertyValue(name).trim();
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            const trendData  = @json($reportTrend);
            const statusData = @json($statusCounts);
            const resolveRate = {{ $resolveRate }};
            const statusColors = { submitted: '--nm-sky', in_progress: '--nm-amber', resolved: '--nm-green', closed: '--nm-grey' };

            const palette = () => ({
                text: css('--nm-ink-2'),
                grid: css('--nm-lo') + '66',
                accent: css('--nm-accent'),
                surface: css('--nm-bg'),
                green: css('--nm-green'),
                status: Object.keys(statusData).map(k => css(statusColors[k] || '--nm-red')),
            });

            Chart.defaults.font.family = "'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif";
            Chart.defaults.font.weight = 600;
            let p = palette();

            // 1. Resolution Dial Chart – always visible, handles 0% / 100% safely
            const dialCtx = document.getElementById('dialChart').getContext('2d');
            const dialChart = new Chart(dialCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Resolved', 'Remaining'],
                    datasets: [{
                        // never pure 0 so Chart.js still draws a tiny segment
                        data: [
                            Math.max(resolveRate, 0.01),
                            Math.max(100 - resolveRate, 0.01)
                        ],
                        backgroundColor: [p.green, p.surface],
                        borderWidth: 0,
                        borderRadius: 10          // rounded ends on both sides of the green arc
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '82%',
                    animation: reduceMotion ? false : undefined,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                }
            });

            // 2. Trend Chart
            const trendChart = new Chart(document.getElementById('trendChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: trendData.map(d => d.label),
                    datasets: [{
                        label: 'Reports',
                        data: trendData.map(d => d.count),
                        backgroundColor: p.accent,
                        borderRadius: 10,
                        borderSkipped: false,
                        barPercentage: 0.55
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: reduceMotion ? false : undefined,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, border: { display: false }, grid: { color: p.grid }, ticks: { color: p.text, stepSize: 1 } },
                        x: { border: { display: false }, grid: { display: false }, ticks: { color: p.text } }
                    }
                }
            });

            // 3. Status Chart
            const statusChart = new Chart(document.getElementById('statusChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(statusData).map(k => k.replace('_', ' ').replace(/^./, c => c.toUpperCase())),
                    datasets: [{
                        data: Object.values(statusData),
                        backgroundColor: p.status,
                        borderWidth: 5,
                        borderColor: p.surface,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: reduceMotion ? false : undefined,
                    cutout: '68%',
                    plugins: {
                        legend: { position: 'bottom', labels: { color: p.text, usePointStyle: true, boxWidth: 8, padding: 14 } }
                    }
                }
            });

            // Re-colour charts when theme flips
            new MutationObserver(() => {
                p = palette();
                
                dialChart.data.datasets[0].backgroundColor = [p.green, p.surface];
                dialChart.update('none');

                trendChart.data.datasets[0].backgroundColor = p.accent;
                trendChart.options.scales.y.grid.color = p.grid;
                trendChart.options.scales.y.ticks.color = p.text;
                trendChart.options.scales.x.ticks.color = p.text;
                trendChart.update('none');

                statusChart.data.datasets[0].backgroundColor = p.status;
                statusChart.data.datasets[0].borderColor = p.surface;
                statusChart.options.plugins.legend.labels.color = p.text;
                statusChart.update('none');
            }).observe(root, { attributes: true, attributeFilter: ['class'] });
        });
    </script>
@endsection