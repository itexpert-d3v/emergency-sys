@extends('admin.partials.ops-theme')

@section('title', 'Operations settings · Kalibo MDRRMO')

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
            <header class="operations-page-head">
                <div>
                    <h1>Settings</h1>
                    <p>Configure report intake, access controls, and audible alerts.</p>
                </div>
            </header>

            @if (session('status'))
                <p class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">{{ session('status') }}</p>
            @endif

            <section class="ops-card mb-5 max-w-3xl p-5 sm:p-6" aria-labelledby="appearance-title">
                <div class="border-b border-slate-300 pb-4 dark:border-slate-700">
                    <h2 id="appearance-title" class="text-base font-black text-slate-900 dark:text-white">Appearance</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Choose the color mode for this browser.</p>
                </div>
                <div class="ops-appearance-options mt-4" role="group" aria-label="Color appearance">
                        <button type="button" class="ops-btn" data-appearance-choice="light" aria-pressed="false">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>Light
                    </button>
                    <button type="button" class="ops-btn" data-appearance-choice="dark" aria-pressed="false">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M20.5 15.5A8.5 8.5 0 0 1 8.5 3.5 8.5 8.5 0 1 0 20.5 15.5Z"/></svg>Dark
                    </button>
                    <button type="button" class="ops-btn" data-appearance-choice="system" aria-pressed="false">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><rect x="3" y="4" width="18" height="13" rx="2"/><path stroke-linecap="round" d="M8 21h8m-4-4v4"/></svg>System
                    </button>
                </div>
            </section>

            <section class="ops-card mb-5 max-w-3xl p-5 sm:p-6" aria-labelledby="command-center-location-title">
                <div class="border-b border-slate-200 pb-4 dark:border-slate-700">
                    <h2 id="command-center-location-title" class="text-base font-black text-slate-900 dark:text-white">Command Center Location</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Set the shared map center and route origin for all workstations.</p>
                </div>
                <form method="POST" action="{{ route('admin.settings.command-center-location.update') }}" class="space-y-4 pt-4">
                    @csrf
                    <fieldset>
                        <legend class="mb-2 text-sm font-bold text-slate-800 dark:text-slate-100">Location source</legend>
                        <div class="flex flex-wrap gap-4">
                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                <input type="radio" name="location_mode" value="automatic" @checked(old('location_mode', $commandCenterLocation['mode']) === 'automatic') class="h-4 w-4 border-slate-300 text-blue-800 focus:ring-blue-800">
                                <span>Automatic location</span>
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                <input type="radio" name="location_mode" value="manual" @checked(old('location_mode', $commandCenterLocation['mode']) === 'manual') class="h-4 w-4 border-slate-300 text-blue-800 focus:ring-blue-800">
                                <span>Manual coordinates</span>
                            </label>
                        </div>
                        @error('location_mode')<p class="mt-1 text-xs font-semibold text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                    </fieldset>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="command-center-latitude" class="mb-1 block text-sm font-bold text-slate-800 dark:text-slate-100">Latitude</label>
                            <input id="command-center-latitude" name="latitude" type="number" min="-90" max="90" step="any" required value="{{ old('latitude', $commandCenterLocation['latitude']) }}" class="ops-input w-full">
                            @error('latitude')<p class="mt-1 text-xs font-semibold text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="command-center-longitude" class="mb-1 block text-sm font-bold text-slate-800 dark:text-slate-100">Longitude</label>
                            <input id="command-center-longitude" name="longitude" type="number" min="-180" max="180" step="any" required value="{{ old('longitude', $commandCenterLocation['longitude']) }}" class="ops-input w-full">
                            @error('longitude')<p class="mt-1 text-xs font-semibold text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button id="detect-command-center-location" type="button" class="ops-btn">Detect this workstation</button>
                        <button type="submit" class="ops-btn ops-btn-primary">Save command center location</button>
                    </div>
                    <p id="command-center-location-status" class="m-0 text-xs text-slate-500 dark:text-slate-400" role="status" aria-live="polite">Selecting automatic location asks for this workstation's location. Save to apply the detected coordinates as the shared map center.</p>
                </form>
            </section>

            <section class="ops-card mt-5 max-w-3xl p-5 sm:p-6" aria-labelledby="command-center-logo-title">
                <div class="border-b border-slate-200 pb-4 dark:border-slate-700">
                    <h2 id="command-center-logo-title" class="text-base font-black text-slate-900 dark:text-white">Command Center Marker Logo</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Upload a JPEG, PNG, or WebP image for the shared live-map marker. Maximum size: 2048 KB (2 MB). HEIC/HEIF photos must be converted first.</p>
                </div>

                @if ($hasCommandCenterLogo)
                    <div class="flex flex-wrap items-center gap-4 border-b border-slate-200 py-4 dark:border-slate-700">
                        <img src="{{ route('operations.live-map.command-center-logo') }}" alt="Current Command Center marker logo" class="h-20 w-20 object-contain">
                        <form method="POST" action="{{ route('admin.settings.command-center-logo.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ops-btn">Use built-in SOU logo</button>
                        </form>
                    </div>
                @else
                    <p class="m-0 border-b border-slate-200 py-3 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">The built-in SOU logo is currently used on the live map.</p>
                @endif

                <form method="POST" action="{{ route('admin.settings.command-center-logo.store') }}" enctype="multipart/form-data" class="space-y-4 pt-4">
                    @csrf
                    <div>
                        <label for="command-center-logo" class="mb-1 block text-sm font-bold text-slate-800 dark:text-slate-100">Choose marker logo</label>
                        <input id="command-center-logo" name="command_center_logo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required aria-describedby="command-center-logo-help" class="block w-full text-sm text-slate-700 file:mr-3 file:min-h-10 file:rounded-md file:border-0 file:bg-blue-800 file:px-4 file:py-2 file:font-bold file:text-white dark:text-slate-200 dark:file:bg-blue-400 dark:file:text-slate-950">
                        <p id="command-center-logo-help" class="mt-1 text-xs text-slate-500 dark:text-slate-400">Photos over 2 MB or in unsupported formats will be rejected. Reduce the image size or convert it to JPEG, PNG, or WebP.</p>
                        @error('command_center_logo')<p class="mt-2 text-sm font-semibold text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="ops-btn ops-btn-primary">Upload marker logo</button>
                </form>
            </section>

            <section class="ops-card max-w-3xl p-5 sm:p-6" aria-labelledby="alarm-settings-title">
                <div class="border-b border-slate-200 pb-4 dark:border-slate-700">
                    <h2 id="alarm-settings-title" class="text-base font-black text-slate-900 dark:text-white">Report acceptance alarm</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">The alarm repeats continuously while any report is waiting for acceptance. Each workstation stores its own sound preference.</p>
                </div>

                <div class="flex flex-col gap-3 border-b border-slate-200 py-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="m-0 text-sm font-bold text-slate-800 dark:text-slate-100">Audible alarm on this workstation</p>
                        <p id="alarm-setting-status" class="mt-1 mb-0 text-xs text-slate-500 dark:text-slate-400" role="status">Loading preference…</p>
                    </div>
                    <label class="inline-flex items-center gap-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        <input id="alarm-enabled" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-blue-800 focus:ring-blue-800 dark:border-slate-600 dark:bg-slate-800">
                        <span>Sound on</span>
                    </label>
                </div>

                <form method="POST" action="{{ route('admin.settings.alarm-audio.store') }}" enctype="multipart/form-data" class="space-y-4 pt-4">
                    @csrf
                    <div>
                        <label for="alarm-audio" class="mb-1 block text-sm font-bold text-slate-800 dark:text-slate-100">Custom alarm file</label>
                        <p class="mb-3 text-xs text-slate-500 dark:text-slate-400">Optional MP3, maximum 5 MB. This sound is shared with other admin workstations.</p>
                        <input id="alarm-audio" name="alarm_audio" type="file" accept=".mp3,audio/mpeg" required class="block w-full text-sm text-slate-700 file:mr-3 file:min-h-10 file:rounded-md file:border-0 file:bg-blue-800 file:px-4 file:py-2 file:font-bold file:text-white dark:text-slate-200 dark:file:bg-blue-400 dark:file:text-slate-950">
                        @error('alarm_audio')
                            <p class="mt-2 text-sm font-semibold text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="ops-btn ops-btn-primary">Upload MP3</button>
                        @if ($hasAlarmAudio)
                            <audio controls preload="none" src="{{ route('operations.live-map.alarm-audio') }}" class="h-10 max-w-full"></audio>
                            <button type="submit" form="remove-alarm-audio" class="ops-btn">Use built-in sound</button>
                        @else
                            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Built-in repeating alert beeps are active.</span>
                        @endif
                    </div>
                </form>

                @if ($hasAlarmAudio)
                    <form id="remove-alarm-audio" method="POST" action="{{ route('admin.settings.alarm-audio.destroy') }}" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif

                <p class="mt-4 mb-0 text-xs leading-5 text-slate-500 dark:text-slate-400">The map starts the alarm automatically when a report is pending. Some browsers block audio until the workstation has received user interaction; opening Settings or using the map will allow it to retry.</p>
            </section>

            <section class="ops-card mt-5 max-w-3xl p-5 sm:p-6" aria-labelledby="report-settings-title">
                <div class="border-b border-slate-200 pb-4 dark:border-slate-700">
                    <h2 id="report-settings-title" class="text-base font-black text-slate-900 dark:text-white">Report intake</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Set validation limits and how long private tracking links remain valid.</p>
                </div>
                <form method="POST" action="{{ route('admin.settings.report-intake.update') }}" class="space-y-4 pt-4">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="description_min_length" class="mb-1 block text-sm font-bold text-slate-800 dark:text-slate-100">Minimum description length</label>
                            <input id="description_min_length" name="description_min_length" type="number" min="1" max="500" required value="{{ old('description_min_length', $reportSettings['description_min_length']) }}" class="ops-input w-full">
                            @error('description_min_length')<p class="mt-1 text-xs font-semibold text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="description_max_length" class="mb-1 block text-sm font-bold text-slate-800 dark:text-slate-100">Maximum description length</label>
                            <input id="description_max_length" name="description_max_length" type="number" min="1" max="5000" required value="{{ old('description_max_length', $reportSettings['description_max_length']) }}" class="ops-input w-full">
                            @error('description_max_length')<p class="mt-1 text-xs font-semibold text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="reporter_name_max_length" class="mb-1 block text-sm font-bold text-slate-800 dark:text-slate-100">Maximum reporter name length</label>
                            <input id="reporter_name_max_length" name="reporter_name_max_length" type="number" min="1" max="200" required value="{{ old('reporter_name_max_length', $reportSettings['reporter_name_max_length']) }}" class="ops-input w-full">
                            @error('reporter_name_max_length')<p class="mt-1 text-xs font-semibold text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="expiry_days" class="mb-1 block text-sm font-bold text-slate-800 dark:text-slate-100">Tracking link validity (days)</label>
                            <input id="expiry_days" name="expiry_days" type="number" min="1" max="30" required value="{{ old('expiry_days', $reportSettings['expiry_days']) }}" class="ops-input w-full">
                            @error('expiry_days')<p class="mt-1 text-xs font-semibold text-red-700 dark:text-red-300" role="alert">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <button type="submit" class="ops-btn ops-btn-primary">Save report settings</button>
                </form>
            </section>

            <section class="ops-card mt-5 max-w-3xl p-5 sm:p-6" aria-labelledby="moderation-link-title">
                <h2 id="moderation-link-title" class="text-base font-black text-slate-900 dark:text-white">Bans and deleted reports</h2>
                <p class="mt-1 mb-4 text-sm text-slate-600 dark:text-slate-300">Review blocked phone numbers and IP addresses, or restore a soft-deleted report.</p>
                <a href="{{ route('admin.moderation.index') }}" class="ops-btn ops-btn-primary">Open moderation</a>
            </section>
@endsection

@section('scripts')
    <script>
        (() => {
            const detectLocationButton = document.getElementById('detect-command-center-location');
            const locationStatus = document.getElementById('command-center-location-status');
            const latitudeInput = document.getElementById('command-center-latitude');
            const longitudeInput = document.getElementById('command-center-longitude');
            const automaticMode = document.querySelector('input[name="location_mode"][value="automatic"]');
            const manualMode = document.querySelector('input[name="location_mode"][value="manual"]');
            let selectedMode = document.querySelector('input[name="location_mode"]:checked')?.value || 'manual';
            let detectingLocation = false;

            function setLocationMode(mode) {
                automaticMode.checked = mode === 'automatic';
                manualMode.checked = mode === 'manual';
                selectedMode = mode;
            }

            function detectLocation(fallbackMode = selectedMode) {
                if (detectingLocation) {
                    return;
                }

                if (!navigator.geolocation) {
                    locationStatus.textContent = 'Geolocation is not supported by this browser. Enter coordinates manually.';
                    setLocationMode(fallbackMode);
                    return;
                }

                detectingLocation = true;
                detectLocationButton.disabled = true;
                locationStatus.textContent = 'Detecting this workstation location…';
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        latitudeInput.value = position.coords.latitude;
                        longitudeInput.value = position.coords.longitude;
                        setLocationMode('automatic');
                        locationStatus.textContent = 'Location detected. Save to apply it as the shared command center location.';
                        detectingLocation = false;
                        detectLocationButton.disabled = false;
                    },
                    (error) => {
                        setLocationMode(fallbackMode);
                        locationStatus.textContent = error.code === error.PERMISSION_DENIED
                            ? 'Location permission was denied. Allow location access or enter coordinates manually.'
                            : 'Location could not be detected. Check location availability or enter coordinates manually.';
                        detectingLocation = false;
                        detectLocationButton.disabled = false;
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            }

            automaticMode.addEventListener('change', () => {
                const previousMode = selectedMode;
                selectedMode = 'automatic';
                detectLocation(previousMode);
            });
            automaticMode.addEventListener('click', () => {
                if (selectedMode === 'automatic') {
                    detectLocation('automatic');
                }
            });
            manualMode.addEventListener('change', () => {
                selectedMode = 'manual';
            });
            detectLocationButton.addEventListener('click', () => detectLocation());

            const toggle = document.getElementById('alarm-enabled');
            const status = document.getElementById('alarm-setting-status');
            const storageKey = 'admin-alarm-sound-enabled';
            const savedPreference = localStorage.getItem(storageKey);

            toggle.checked = savedPreference !== 'false';
            status.textContent = toggle.checked ? 'Enabled on this workstation.' : 'Muted on this workstation.';

            toggle.addEventListener('change', () => {
                localStorage.setItem(storageKey, String(toggle.checked));
                status.textContent = toggle.checked ? 'Enabled on this workstation.' : 'Muted on this workstation.';
            });
        })();
    </script>
@endsection