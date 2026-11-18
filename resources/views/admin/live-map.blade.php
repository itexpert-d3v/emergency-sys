@extends('admin.partials.ops-theme')

@section('title', 'Live Triage Map · Admin')

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endsection

@section('styles')
    <style>
        /* ---------- Map ---------- */
        #live-map { height: calc(100vh - 140px); width: 100%; border-radius: 12px; z-index: 1; transition: all 0.3s ease; }
        #map-wrapper:fullscreen { padding: 0; border-radius: 0; background: #18181b; }
        #map-wrapper:fullscreen #live-map { height: 100vh; border-radius: 0; }

        .custom-pin { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); color: white; box-shadow: 0 8px 12px -2px rgba(0, 0, 0, 0.3); animation: pin-bounce 2s infinite; border: 3px solid #fff; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; }
        .custom-pin:hover { transform: rotate(-45deg) scale(1.1); }
        .custom-pin.active { transform: rotate(-45deg) scale(1.2); box-shadow: 0 0 0 6px rgba(59, 130, 246, 0.3), 0 8px 12px rgba(0,0,0,0.4); z-index: 1000 !important; }
        .custom-pin svg { width: 18px; height: 18px; transform: rotate(45deg); stroke-width: 2.5; }

        .custom-pin.priority-critical { background-color: #ef4444; }
        .custom-pin.priority-high { background-color: #f97316; }
        .custom-pin.priority-medium { background-color: #eab308; }
        .custom-pin.priority-low { background-color: #3b82f6; }

        @keyframes pin-bounce { 0%, 100% { transform: rotate(-45deg) translateY(0); } 50% { transform: rotate(-45deg) translateY(-10px); } }

        /* ---------- Sync badge ---------- */
        #sync-badge { position: relative; display: flex; align-items: center; gap: 8px; padding: 4px 12px; border-radius: 999px; background-color: #ecfdf5; color: #047857; font-size: 0.75rem; font-weight: 700; transition: all 0.3s ease; border: 1px solid #a7f3d0; }
        .dark #sync-badge { background-color: rgba(6, 78, 59, 0.5); color: #34d399; border-color: #065f46; }
        .sync-dot { position: relative; display: block; width: 8px; height: 8px; background-color: #10b981; border-radius: 50%; }
        .sync-dot::after { content: ''; position: absolute; top: 50%; left: 50%; width: 8px; height: 8px; background-color: #10b981; border-radius: 50%; transform: translate(-50%, -50%); animation: radar 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes radar { 0% { transform: translate(-50%, -50%) scale(1); opacity: 1; } 100% { transform: translate(-50%, -50%) scale(4); opacity: 0; } }
        .sync-pulse { background-color: #10b981 !important; color: #ffffff !important; border-color: #10b981 !important; box-shadow: 0 0 15px rgba(16, 185, 129, 0.4); }
        .sync-pulse .sync-dot { background-color: #ffffff; }
        .sync-pulse .sync-dot::after { background-color: #ffffff; animation-duration: 0.5s; }

        /* ---------- Acceptance card ---------- */
        .acceptance-alert { border-color: #dc2626 !important; animation: acceptance-pulse .9s ease-in-out infinite; }
        .acceptance-alert .acceptance-light { background: #dc2626; animation: acceptance-beacon .65s ease-in-out infinite alternate; }
        .acceptance-alert #accept-report { animation: acceptance-button .9s ease-in-out infinite alternate; }
        @keyframes acceptance-pulse { 0%, 100% { box-shadow: 0 4px 14px rgba(15, 23, 42, .16); } 50% { box-shadow: 0 6px 24px rgba(220, 38, 38, .55); } }
        @keyframes acceptance-beacon { from { box-shadow: 0 0 0 2px rgba(220, 38, 38, .25); } to { box-shadow: 0 0 0 8px rgba(220, 38, 38, .08); } }
        @keyframes acceptance-button { from { background-color: #b91c1c; } to { background-color: #7f1d1d; } }
        @media (prefers-reduced-motion: reduce) { .acceptance-alert, .acceptance-alert .acceptance-light, .acceptance-alert #accept-report, .custom-pin { animation: none; } }

        .leaflet-tooltip.custom-map-tooltip { background: #18181b; color: #fff; border-radius: 6px; padding: 6px 10px; border: 0; box-shadow: 0 4px 6px rgba(0,0,0,0.3); font-weight: bold; font-size: 12px; display: flex; align-items: center; gap: 6px; }
        .leaflet-tooltip-top:before { border-top-color: #18181b; }
        .dark .leaflet-layer, .dark .leaflet-control-zoom-in, .dark .leaflet-control-zoom-out { filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%); }

        .route-distance-tooltip { background: rgba(255, 255, 255, 0.95) !important; border: 1px solid #3b82f6 !important; color: #1e3a8a !important; font-weight: bold !important; font-size: 11px !important; border-radius: 6px !important; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important; padding: 4px 8px !important; }
        .route-distance-tooltip::before { display: none; }

        /* ---------- Map popup (Open report) ---------- */
        .lt-popup .leaflet-popup-content-wrapper { padding:0; overflow:hidden; border:1px solid var(--o-line); border-radius:14px; background:var(--o-surface); color:var(--o-ink); box-shadow:0 12px 28px -6px rgba(0,0,0,.28); font-family:ui-sans-serif, system-ui, sans-serif; }
        .lt-popup .leaflet-popup-content { margin:0; line-height:1.4; }
        .lt-popup .leaflet-popup-tip { background:var(--o-surface); border:1px solid var(--o-line); }
        .lt-popup a.leaflet-popup-close-button { top:.625rem; right:.625rem; color:var(--o-ink-3); }
        .lt-pop-head { display:flex; align-items:center; gap:.75rem; padding:.875rem 2.25rem .75rem .875rem; border-bottom:1px solid var(--o-line); }
        .lt-popup .lt-pop-title { margin:0; font-size:.9375rem; font-weight:700; color:var(--o-ink); }
        .lt-popup .lt-pop-ref { margin:0; font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-size:.75rem; color:var(--o-ink-3); }
        .lt-pop-body { padding:.75rem .875rem; }
        .lt-popup .lt-pop-loc { display:flex; gap:.375rem; margin:0 0 .625rem; font-size:.8125rem; color:var(--o-ink-2); }
        .lt-pop-loc svg { flex:none; width:1rem; height:1rem; margin-top:.1rem; color:var(--o-ink-3); }
        .lt-pop-meta { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; }
        .lt-pop-actions { display:grid; grid-template-columns:1fr auto; gap:.5rem; padding:0 .875rem .875rem; }
    </style>
@endsection

@section('content')

        <header class="operations-page-head flex-wrap gap-4">
            <div>
                <p class="operations-eyebrow">Operations · Live</p>
                <h1>Live Incident Map</h1>
                <p>Live incident locations and pending acceptance alerts.</p>
            </div>
            <div class="operations-head-actions relative">
                <span id="sync-badge" title="Live connection active"><span class="sync-dot"></span> Live Sync</span>
            </div>
        </header>

        <div class="h-[calc(100vh-140px)]">
            <div id="map-wrapper" class="ops-card h-full p-1 relative">
                <section id="acceptance-card" class="absolute top-4 right-20 z-[9999] w-80 max-w-[calc(100%-5rem)] max-h-[58vh] overflow-y-auto rounded-lg border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900/95" aria-label="Report acceptance">
                    <div class="flex items-center gap-2">
                        <span class="acceptance-light h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-500"></span>
                        <p id="acceptance-state" class="m-0 text-xs font-black text-slate-800 dark:text-slate-100" aria-live="assertive">All reports accepted</p>
                    </div>
                    <div id="acceptance-details" class="mt-2 hidden space-y-2 border-t border-slate-200 pt-2 text-xs dark:border-slate-700">
                        <div class="flex flex-wrap items-baseline justify-between gap-1">
                            <strong id="acceptance-reference" class="font-mono text-slate-900 dark:text-slate-100"></strong>
                            <span id="acceptance-meta" class="text-slate-500 dark:text-slate-400"></span>
                        </div>
                        <p id="acceptance-description" class="m-0 max-h-20 overflow-y-auto whitespace-pre-wrap leading-5 text-slate-700 dark:text-slate-200"></p>
                        <dl class="grid grid-cols-[4.5rem_minmax(0,1fr)] gap-x-2 gap-y-1 text-slate-600 dark:text-slate-300">
                            <dt>Location</dt><dd id="acceptance-location" class="m-0 break-words"></dd>
                            <dt>Reporter</dt><dd id="acceptance-reporter" class="m-0 break-words"></dd>
                            <dt>Contact</dt><dd class="m-0"><a id="acceptance-contact" class="font-semibold text-blue-800 underline underline-offset-2 dark:text-blue-300"></a></dd>
                        </dl>
                        <a id="acceptance-open-report" class="inline-flex font-bold text-blue-800 underline underline-offset-2 dark:text-blue-300" href="#">Open full report</a>
                    </div>
                    <p id="acceptance-empty" class="mt-1 mb-2 text-[11px] text-slate-500 dark:text-slate-400">No reports awaiting acceptance</p>
                    
                    <div class="mt-2 flex gap-2" style="display: {{ auth()->user()->role === 'admin' ? 'flex' : 'none' }}">
                        <button id="accept-report" type="button" class="flex-1 rounded-md bg-red-700 px-3 py-2 text-xs font-bold text-white transition hover:bg-red-800 disabled:cursor-not-allowed disabled:bg-slate-400 dark:disabled:bg-slate-700" disabled>Accept report</button>
                        <button id="reject-report" type="button" class="flex-1 rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700" disabled>Reject</button>
                    </div>
                </section>
                <button id="fullscreen-btn" class="absolute top-4 right-4 z-[9999] flex h-10 w-10 items-center justify-center rounded-lg bg-white/90 text-slate-700 shadow-lg backdrop-blur-md transition hover:bg-white dark:bg-slate-800/90 dark:text-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-600" title="Toggle Fullscreen" style="pointer-events: auto;">
                    <svg id="expand-icon" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" /></svg>
                    <svg id="collapse-icon" class="hidden h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 14h4m0 0v4m0-4l-5 5m13-13h-4m0 0V4m0 4l5-5M4 10h4m0 0V6m0 4l-5-5m13 13h-4m0 0v4m0-4l5 5" /></svg>
                </button>
                <div id="live-map"></div>
            </div>
        </div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        /* ============================== Map setup ============================== */
        const kaliboLat = 11.7061;
        const kaliboLng = 122.3687;
        const kaliboBounds = L.latLngBounds([11.5800, 122.2000], [11.8300, 122.5500]);

        const map = L.map('live-map', { center: [kaliboLat, kaliboLng], zoom: 14, minZoom: 12, maxBounds: kaliboBounds, maxBoundsViscosity: 1.0 });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);

        const urlParams = new URLSearchParams(window.location.search);
        const targetReportId = urlParams.get('report_id');

        const commandCenterLat = 11.7061;
        const commandCenterLng = 122.3687;
        let currentRouteLayer = null;
        let hasAttemptedFocus = false;

        // Using config() pulls from the env file directly, bypassing cache issues.
        const navigationApiKey = "{{ config('services.openrouteservice.key', env('NAVIGATION_API', '')) }}";

        if (!navigationApiKey) {
            console.warn("NAVIGATION_API is empty. Please add it to your .env file and run 'php artisan config:clear'.");
        }

        /* ============================== Routing ============================== */
        async function drawRoute(startLat, startLng, endLat, endLng) {
            if (currentRouteLayer) map.removeLayer(currentRouteLayer);

            if (!navigationApiKey) {
                drawFallbackLine(startLat, startLng, endLat, endLng);
                return;
            }

            try {
                const url = `https://api.openrouteservice.org/v2/directions/driving-car?api_key=${navigationApiKey}&start=${startLng},${startLat}&end=${endLng},${endLat}`;

                const response = await fetch(url);
                if (!response.ok) {
                    const err = await response.json();
                    throw new Error(err.error || 'Routing API failed');
                }

                const data = await response.json();

                if (data.features && data.features.length > 0) {
                    const feature = data.features[0];
                    const coordinates = feature.geometry.coordinates.map(coord => [coord[1], coord[0]]);

                    currentRouteLayer = L.polyline(coordinates, {
                        color: '#3b82f6', weight: 5, opacity: 0.8, lineCap: 'round'
                    }).addTo(map);

                    const distanceKm = (feature.properties.segments[0].distance / 1000).toFixed(2);
                    const durationMins = Math.round(feature.properties.segments[0].duration / 60);

                    currentRouteLayer.bindTooltip(`<b>${distanceKm} km</b> · ~${durationMins} mins`, {
                        permanent: true, direction: 'center', className: 'route-distance-tooltip'
                    }).openTooltip();

                    map.fitBounds(currentRouteLayer.getBounds(), { padding: [50, 50] });
                } else {
                    throw new Error('No route found');
                }
            } catch (e) {
                console.error("Routing API failed:", e.message);
                drawFallbackLine(startLat, startLng, endLat, endLng);
            }
        }

        function drawFallbackLine(startLat, startLng, endLat, endLng) {
            if (currentRouteLayer) map.removeLayer(currentRouteLayer);
            currentRouteLayer = L.polyline([[startLat, startLng], [endLat, endLng]], {
                color: '#3b82f6', weight: 4, opacity: 0.8, dashArray: '10, 10', lineCap: 'round'
            }).addTo(map);

            const R = 6371;
            const dLat = (endLat - startLat) * Math.PI / 180;
            const dLon = (endLng - startLng) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(startLat * Math.PI / 180) * Math.cos(endLat * Math.PI / 180) * Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            const distance = (R * c).toFixed(2);

            currentRouteLayer.bindTooltip(`Est. Distance: ${distance} km (Simulated Route)`, {
                permanent: true, direction: 'center', className: 'route-distance-tooltip'
            }).openTooltip();

            map.fitBounds(currentRouteLayer.getBounds(), { padding: [50, 50] });
        }

        // Used by the "Route" button inside the map popup
        window.ltRoute = (lat, lng) => {
            map.closePopup();
            drawRoute(commandCenterLat, commandCenterLng, lat, lng);
        };

        /* ============================== Fullscreen ============================== */
        const mapWrapper = document.getElementById('map-wrapper');
        const fullscreenBtn = document.getElementById('fullscreen-btn');
        const expandIcon = document.getElementById('expand-icon');
        const collapseIcon = document.getElementById('collapse-icon');
        fullscreenBtn.addEventListener('click', () => {
            if (!document.fullscreenElement) { if (mapWrapper.requestFullscreen) mapWrapper.requestFullscreen(); else if (mapWrapper.webkitRequestFullscreen) mapWrapper.webkitRequestFullscreen(); } else { if (document.exitFullscreen) document.exitFullscreen(); }
        });
        document.addEventListener('fullscreenchange', () => {
            if (document.fullscreenElement) { expandIcon.classList.add('hidden'); collapseIcon.classList.remove('hidden'); } else { collapseIcon.classList.add('hidden'); expandIcon.classList.remove('hidden'); }
            setTimeout(() => map.invalidateSize(), 100);
        });

        /* ============================== State ============================== */
        const markers = {};
        const knownReportIds = new Set();
        let initialLoadDone = false;
        let pendingAcceptReports = [];

        /* ============================== Acceptance control ============================== */
        const acceptanceCard = document.getElementById('acceptance-card');
        const acceptanceState = document.getElementById('acceptance-state');
        const acceptanceReference = document.getElementById('acceptance-reference');
        const acceptanceDetails = document.getElementById('acceptance-details');
        const acceptanceEmpty = document.getElementById('acceptance-empty');
        const acceptReportButton = document.getElementById('accept-report');
        const rejectReportButton = document.getElementById('reject-report');
        
        const acceptUrlTemplate = @json(route('admin.report.accept', ['report' => '__REPORT_ID__']));
        const rejectUrlTemplate = @json(route('admin.report.reject', ['report' => '__REPORT_ID__']));
        
        const alarmAudioUrl = @json($alarmAudioUrl);
        const alarmPreferenceKey = 'admin-alarm-sound-enabled';
        let alarmAudioContext = null;
        let alarmAudioElement = null;
        let alarmOscillators = null;
        let alarmToneTimer = null;
        let alarmRecoveryTimer = null;

        function getAlarmAudioContext() {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return null;
            if (!alarmAudioContext || alarmAudioContext.state === 'closed') {
                alarmAudioContext = new AudioContext();
                alarmAudioContext.addEventListener('statechange', () => {
                    if (alarmAudioContext.state === 'running' && pendingAcceptReports.length && !alarmAudioUrl) {
                        startBuiltInAlarm();
                    }
                });
            }
            return alarmAudioContext;
        }

        function alarmSoundEnabled() {
            return localStorage.getItem(alarmPreferenceKey) !== 'false';
        }

        function startBuiltInAlarm() {
            const context = getAlarmAudioContext();
            if (!context) return;
            if (alarmAudioContext.state !== 'running') {
                alarmAudioContext.resume().then(() => {
                    if (pendingAcceptReports.length && alarmSoundEnabled()) startBuiltInAlarm();
                }).catch(() => {});
                return;
            }
            if (alarmOscillators) return;

            const primary = alarmAudioContext.createOscillator();
            const overtone = alarmAudioContext.createOscillator();
            const gain = alarmAudioContext.createGain();
            primary.type = 'square';
            overtone.type = 'sine';
            primary.frequency.value = 880;
            overtone.frequency.value = 1320;
            gain.gain.value = 0.0001;
            primary.connect(gain);
            overtone.connect(gain);
            gain.connect(alarmAudioContext.destination);
            primary.start();
            overtone.start();
            alarmOscillators = [primary, overtone, gain];

            const alarmStartedAt = alarmAudioContext.currentTime;
            let currentStep = -1;
            alarmToneTimer = window.setInterval(() => {
                if (!pendingAcceptReports.length || !alarmOscillators) return;
                const time = alarmAudioContext.currentTime;
                const elapsed = (time - alarmStartedAt) % 2.2;
                const step = elapsed < 0.28 ? 0 : elapsed >= 0.42 && elapsed < 0.70 ? 1 : elapsed >= 0.84 && elapsed < 1.12 ? 2 : -1;
                if (step === currentStep) return;
                currentStep = step;
                if (step >= 0) {
                    const frequency = step === 1 ? 660 : 880;
                    primary.frequency.setTargetAtTime(frequency, time, 0.015);
                    overtone.frequency.setTargetAtTime(frequency * 1.5, time, 0.015);
                    gain.gain.setTargetAtTime(0.18, time, 0.015);
                } else {
                    gain.gain.setTargetAtTime(0.0001, time, 0.015);
                }
            }, 50);
        }

        function startAlarmSound() {
            if (!pendingAcceptReports.length || !alarmSoundEnabled()) return;
            if (!alarmRecoveryTimer) {
                alarmRecoveryTimer = window.setInterval(() => {
                    if (!pendingAcceptReports.length || !alarmSoundEnabled()) return;
                    if (alarmAudioUrl) {
                        if (alarmAudioElement?.error || alarmAudioElement?.ended) {
                            startBuiltInAlarm();
                        } else if (alarmAudioElement?.paused) {
                            alarmAudioElement.play().catch(() => startBuiltInAlarm());
                        }
                        return;
                    }

                    const context = getAlarmAudioContext();
                    if (context?.state === 'suspended') {
                        context.resume().then(startBuiltInAlarm).catch(() => {});
                    } else if (context?.state === 'running' && !alarmOscillators) {
                        startBuiltInAlarm();
                    }
                }, 2000);
            }
            if (alarmAudioUrl) {
                alarmAudioElement ??= new Audio(alarmAudioUrl);
                alarmAudioElement.loop = true;
                alarmAudioElement.volume = 0.85;
                alarmAudioElement.addEventListener('error', startBuiltInAlarm, { once: true });
                if (alarmAudioElement.paused) {
                    alarmAudioElement.play().catch(() => startBuiltInAlarm());
                }
                return;
            }
            startBuiltInAlarm();
        }

        function stopAlarmSound() {
            if (alarmAudioElement) {
                alarmAudioElement.pause();
                alarmAudioElement.currentTime = 0;
            }
            if (alarmToneTimer !== null) {
                window.clearInterval(alarmToneTimer);
                alarmToneTimer = null;
            }
            if (alarmRecoveryTimer !== null) {
                window.clearInterval(alarmRecoveryTimer);
                alarmRecoveryTimer = null;
            }
            if (alarmOscillators) {
                alarmOscillators[0].stop();
                alarmOscillators[1].stop();
                alarmOscillators.forEach(node => node.disconnect());
                alarmOscillators = null;
            }
        }

        function updateAcceptanceControl(pendingReports) {
            pendingAcceptReports = pendingReports;

            if (pendingReports.length > 0) {
                const nextReport = pendingReports[0];
                acceptanceCard.classList.add('acceptance-alert');
                acceptanceState.textContent = `${pendingReports.length} report${pendingReports.length === 1 ? '' : 's'} awaiting acceptance`;
                acceptanceDetails.classList.remove('hidden');
                acceptanceEmpty.classList.add('hidden');
                acceptanceReference.textContent = nextReport.reference_code;
                document.getElementById('acceptance-meta').textContent = `${nextReport.category} · ${fmtStatus(nextReport.priority)} priority · ${fmtStatus(nextReport.status)}`;
                document.getElementById('acceptance-description').textContent = nextReport.description || 'No description provided.';
                document.getElementById('acceptance-location').textContent = nextReport.location || 'Not provided';
                document.getElementById('acceptance-reporter').textContent = nextReport.reporter_name || 'Not provided';
                const contactLink = document.getElementById('acceptance-contact');
                contactLink.textContent = nextReport.contact_number || 'Not provided';
                contactLink.href = nextReport.contact_number ? `tel:${nextReport.contact_number}` : '#';
                document.getElementById('acceptance-open-report').href = openUrl(nextReport.id);
                acceptReportButton.disabled = false;
                rejectReportButton.disabled = false;
                startAlarmSound();
                return;
            }
            acceptanceCard.classList.remove('acceptance-alert');
            acceptanceState.textContent = 'All reports accepted';
            acceptanceDetails.classList.add('hidden');
            acceptanceEmpty.classList.remove('hidden');
            acceptReportButton.disabled = true;
            rejectReportButton.disabled = true;
            stopAlarmSound();
        }

        document.addEventListener('pointerdown', startAlarmSound, { passive: true });
        document.addEventListener('keydown', startAlarmSound);
        window.addEventListener('pageshow', () => {
            if (pendingAcceptReports.length && alarmSoundEnabled()) startAlarmSound();
        });
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && pendingAcceptReports.length && alarmSoundEnabled()) startAlarmSound();
        });
        window.addEventListener('pagehide', () => {
            if (alarmAudioElement) alarmAudioElement.pause();
            if (alarmToneTimer !== null) {
                window.clearInterval(alarmToneTimer);
                alarmToneTimer = null;
            }
            if (alarmOscillators) {
                alarmOscillators.slice(0, 2).forEach(node => {
                    try { node.stop(); } catch {}
                    node.disconnect();
                });
                alarmOscillators = null;
            }
            if (alarmAudioContext?.state === 'running') alarmAudioContext.suspend().catch(() => {});
        });
        window.addEventListener('storage', (event) => {
            if (event.key !== alarmPreferenceKey) return;
            if (alarmSoundEnabled()) startAlarmSound();
            else stopAlarmSound();
        });

        acceptReportButton.addEventListener('click', async () => {
            const report = pendingAcceptReports[0];
            if (!report) return;
            acceptReportButton.disabled = true;
            rejectReportButton.disabled = true;
            try {
                const response = await fetch(acceptUrlTemplate.replace('__REPORT_ID__', encodeURIComponent(report.id)), {
                    method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
                if (!response.ok) throw new Error('Acceptance failed');
                const result = await response.json();
                if (result.accepted) {
                    window.location.assign(result.redirect_url);
                } else {
                    await fetchLiveData();
                }
            } catch (error) {
                acceptanceState.textContent = 'Could not accept report. Try again.';
                console.error('Failed to accept report', error);
            } finally {
                acceptReportButton.disabled = pendingAcceptReports.length === 0;
                rejectReportButton.disabled = pendingAcceptReports.length === 0;
            }
        });

        rejectReportButton.addEventListener('click', async () => {
            const report = pendingAcceptReports[0];
            if (!report) return;
            
            const reason = prompt(`Reject report ${report.reference_code}?\n\nPlease enter a reason for rejection (required):`);
            if (reason === null) return; // User cancelled
            if (reason.trim() === '') {
                alert('A rejection reason is required.');
                return;
            }

            acceptReportButton.disabled = true;
            rejectReportButton.disabled = true;
            
            try {
                const formData = new URLSearchParams();
                formData.append('rejection_reason', reason);

                const response = await fetch(rejectUrlTemplate.replace('__REPORT_ID__', encodeURIComponent(report.id)), {
                    method: 'POST', 
                    headers: { 
                        Accept: 'application/json', 
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
                    },
                    body: formData
                });
                
                if (!response.ok) throw new Error('Rejection failed');
                await fetchLiveData();
            } catch (error) {
                acceptanceState.textContent = 'Could not reject report. Try again.';
                console.error('Failed to reject report', error);
            } finally {
                acceptReportButton.disabled = pendingAcceptReports.length === 0;
                rejectReportButton.disabled = pendingAcceptReports.length === 0;
            }
        });

        /* ============================== Helpers ============================== */
        function esc(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }

        const OPEN_BASE = @json($queueUrl);
        const openUrl = id => `${OPEN_BASE}?expand=${encodeURIComponent(id)}`;
        const safePrio = p => ['critical', 'high', 'medium', 'low'].includes(String(p).toLowerCase()) ? String(p).toLowerCase() : 'low';
        const fmtStatus = s => { const t = String(s ?? '').replace(/_/g, ' '); return t.charAt(0).toUpperCase() + t.slice(1); };

        const PIN_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.2-7-11a7 7 0 0114 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>';

        function getCategorySvg(category) {
            const cat = String(category).toLowerCase();
            let path = '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>';
            if (cat.includes('fire')) path = '<path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/>';
            else if (cat.includes('accident') || cat.includes('vehicular')) path = '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8m-8 4h8m-4 4v4m-4-4v4m8-4v4m-4-12v4"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 16v2a2 2 0 002 2h10a2 2 0 002-2v-2l-2-6H7l-2 6z"/>';
            else if (cat.includes('flood')) path = '<path stroke-linecap="round" stroke-linejoin="round" d="M4 16a4 4 0 014-4h8a4 4 0 010 8H8a4 4 0 01-4-4zm0 0a4 4 0 014-4m12 4a4 4 0 00-4-4m-4 0a4 4 0 00-4 4m8-4V4m-4 4v4"/>';
            else if (cat.includes('medical') || cat.includes('health')) path = '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>';
            else if (cat.includes('crime') || cat.includes('police')) path = '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>';
            return `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">${path}</svg>`;
        }

        function getStatusBadge(status) {
            status = String(status).toLowerCase().replace(' ', '_');
            if (status === 'submitted') return 'b-sky';
            if (status === 'in_progress') return 'b-amber';
            if (status === 'resolved') return 'b-emerald';
            return 'b-zinc';
        }

        function showDesktopNotification(title, body) {
            if (!("Notification" in window)) return;
            if (Notification.permission === "granted") new Notification(title, { body: body, icon: '/assets/lgu kalibo.jpg' });
        }

        /* ============================== Templates ============================== */
        function popupHtml(report, svgIcon) {
            const p = safePrio(report.priority);
            return `
            <div class="pr-${p}">
                <div class="lt-pop-head">
                    <div class="lt-tile">${svgIcon}</div>
                    <div class="min-w-0">
                        <p class="lt-pop-title">${esc(report.category)}</p>
                        <p class="lt-pop-ref">${esc(report.reference_code)}</p>
                    </div>
                </div>
                <div class="lt-pop-body">
                    <p class="lt-pop-loc">${PIN_SVG}<span>${esc(report.location || 'Location not provided')}</span></p>
                    <div class="lt-pop-meta">
                        <span class="ops-badge ${getStatusBadge(report.status)}">${esc(fmtStatus(report.status))}</span>
                        <span class="ops-prio">${fmtStatus(p)} priority</span>
                        <span class="lt-time">${esc(report.time || '')}</span>
                    </div>
                </div>
                <div class="lt-pop-actions">
                    <a class="ops-btn ops-btn-primary" href="${openUrl(report.id)}">Open report</a>
                    <button type="button" class="ops-btn" onclick="window.ltRoute(${Number(report.lat)}, ${Number(report.lng)})">Route</button>
                </div>
            </div>`;
        }

        function hasCoordinates(report) {
            return report.lat !== null && report.lat !== undefined && report.lng !== null && report.lng !== undefined
                && Number.isFinite(Number(report.lat)) && Number.isFinite(Number(report.lng));
        }

        /* ============================== Fetch + render ============================== */
        async function fetchLiveData() {
            try {
                const response = await fetch('{{ route('operations.live-map.data') }}', { cache: 'no-store' });
                const data = await response.json();

                const syncBadge = document.getElementById('sync-badge');
                if (syncBadge) { syncBadge.classList.add('sync-pulse'); setTimeout(() => syncBadge.classList.remove('sync-pulse'), 800); }

                const reports = data.reports || [];
                updateAcceptanceControl(data.pending_reports || []);

                const activeIds = reports.map(report => String(report.id));
                const newReports = reports.filter(report => !knownReportIds.has(String(report.id)));

                reports.forEach(report => {
                    const svgIcon = getCategorySvg(report.category);
                    const priorityClass = safePrio(report.priority);

                    if (hasCoordinates(report) && !markers[report.id]) {
                        const icon = L.divIcon({ className: 'custom-div-icon', html: `<div class="custom-pin priority-${priorityClass}">${svgIcon}</div>`, iconSize: [36, 36], iconAnchor: [18, 36], popupAnchor: [0, -36] });
                        const marker = L.marker([report.lat, report.lng], { icon: icon }).addTo(map);
                        marker.bindTooltip(`<svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg> NEW INCIDENT`, { permanent: false, direction: 'top', className: 'custom-map-tooltip', offset: [0, -36] });
                        marker.bindPopup(popupHtml(report, svgIcon), { className: 'lt-popup', minWidth: 260, maxWidth: 300 });
                        markers[report.id] = marker;
                    } else if (hasCoordinates(report) && markers[report.id]) {
                        const markerEl = markers[report.id].getElement();
                        if (markerEl) {
                            const pinEl = markerEl.firstElementChild;
                            [...pinEl.classList].forEach(cls => { if (cls.startsWith('priority-')) pinEl.classList.remove(cls); });
                            pinEl.classList.add(`priority-${priorityClass}`);
                        }
                        markers[report.id].setPopupContent(popupHtml(report, svgIcon));
                    } else if (markers[report.id]) {
                        map.removeLayer(markers[report.id]);
                        delete markers[report.id];
                    }
                });

                for (let id in markers) { if (!activeIds.includes(String(id))) { map.removeLayer(markers[id]); delete markers[id]; } }

                if (newReports.length > 0 && initialLoadDone) {
                    const newest = newReports[0];
                    showDesktopNotification(`New ${String(newest.priority).toUpperCase()} Report`, `${newest.category} at ${newest.location}`);
                    if (markers[newest.id]) { markers[newest.id].openTooltip(); setTimeout(() => { if (markers[newest.id]) markers[newest.id].closeTooltip(); }, 6000); }
                }
                if (!initialLoadDone) initialLoadDone = true;
                knownReportIds.clear();
                activeIds.forEach(id => knownReportIds.add(id));

                if (targetReportId && !hasAttemptedFocus) {
                    const reportExists = reports.find(r => String(r.id) === String(targetReportId));
                    if (reportExists && hasCoordinates(reportExists)) {
                        hasAttemptedFocus = true;
                        map.flyTo([reportExists.lat, reportExists.lng], 17, { animate: true, duration: 1 });
                        markers[reportExists.id]?.openPopup();
                        drawRoute(commandCenterLat, commandCenterLng, reportExists.lat, reportExists.lng);
                    }
                }

            } catch (error) {
                console.error("Failed to fetch live data", error);
            }
        }

        fetchLiveData();
        setInterval(fetchLiveData, 3000);
    });
</script>
@endsection