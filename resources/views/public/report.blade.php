<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.theme-init')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Report an incident') }}</title>
    
    {{-- Leaflet Map CSS --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        #map { height: 320px; width: 100%; border-radius: 8px; z-index: 1; }
        /* Fix for Leaflet dark mode controls */
        .dark .leaflet-layer, .dark .leaflet-control-zoom-in, .dark .leaflet-control-zoom-out { filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%); }
    </style>
</head>
<body class="public-surface public-ambulance-surface min-h-screen bg-slate-50 text-slate-800 antialiased transition-colors duration-200 dark:bg-[#18181b] dark:text-slate-100" style="background-image:url('{{ asset('assets/ambulance.jpg') }}');background-size:cover;background-position:center;background-attachment:fixed">
    <div class="app-photo-canvas min-h-screen">
        @include('public.navigation')
        <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
            <header class="civic-panel mb-5 grid gap-5 rounded-[12px] p-5 dark:bg-slate-950/95 dark:shadow-black/30 lg:grid-cols-[minmax(16rem,0.7fr)_minmax(0,1.3fr)] lg:items-center">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ __('Report an incident') }}</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        {{ __('Share the key details and a responder can review the report quickly.') }}
                    </p>
                </div>
                <div class="border-t border-slate-200 pt-4 dark:border-slate-700 lg:border-l lg:border-t-0 lg:pl-5 lg:pt-0">
                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ __('Before you submit') }}</p>
                    <ul class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-3 dark:text-slate-300">
                        <li>• {{ __('Describe what happened clearly and with facts.') }}</li>
                        <li>• {{ __('Include the nearest location or landmark.') }}</li>
                        <li>• {{ __('Use the tracking link to follow updates after submission.') }}</li>
                    </ul>
                </div>
            </header>

            <main class="civic-panel rounded-[12px] p-5 dark:bg-slate-950/95 dark:shadow-black/30 sm:p-7">
                <div id="form-alert" class="hidden rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800/60 dark:bg-amber-950/30 dark:text-amber-200" aria-live="polite"></div>

                <form id="report-form" action="{{ route('public.report.store') }}" method="post" enctype="multipart/form-data" class="mt-4 space-y-5">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <input type="hidden" name="language" value="{{ app()->getLocale() }}">
                    
                    @if($errors->any())
                        <div class="rounded-[4px] border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
                            <p class="font-bold">{{ __('Please check the highlighted report details and try again.') }}</p>
                            <ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif
                    
                    <fieldset class="space-y-4" aria-labelledby="incident-heading">
                        <legend id="incident-heading" class="flex flex-col gap-3 text-lg font-black text-slate-900 sm:flex-row sm:items-center sm:justify-between dark:text-white">
                            <span>{{ __('Report details') }}</span>
                            <span class="w-full max-w-xs">
                                <span data-report-progress-label class="block text-right text-xs font-semibold text-slate-500 dark:text-slate-400">0 of 4 required details complete</span>
                                <span class="mt-1 block h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700" role="progressbar" aria-label="Report completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                                    <span data-report-progress-bar class="block h-full w-0 rounded-full bg-blue-800 transition-[width] duration-200 dark:bg-blue-400"></span>
                                </span>
                            </span>
                        </legend>
                        
                        <div>
                            <label for="description" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('What happened?') }}</label>
                            <textarea id="description" name="description" rows="6" minlength="20" maxlength="2000" title="Describe what happened, what is affected, and any immediate danger. At least 20 characters." class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-800 focus:ring-4 focus:ring-blue-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-900/60" placeholder="{{ __('Describe what happened, who or what is affected, and the help needed.') }}" required>{{ old('description') }}</textarea>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('Include the facts that help the team understand the situation quickly.') }}</p>
                        </div>
                    </fieldset>

                    <fieldset class="space-y-4 border-t border-slate-200 pt-4 dark:border-slate-700" aria-labelledby="contact-heading">
                        <legend id="contact-heading" class="text-lg font-black text-slate-900 dark:text-white">{{ __('Your contact') }}</legend>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="reporter_name" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('Reporter name') }}</label>
                                <input id="reporter_name" name="reporter_name" type="text" pattern="[\p{L}\p{M}]+([ .'-]+[\p{L}\p{M}]+)*\.?" title="Use letters only, with spaces, hyphens, apostrophes, or periods as separators." class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-800 focus:ring-4 focus:ring-blue-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-900/60" placeholder="{{ __('Full name') }}" value="{{ old('reporter_name') }}" required>
                            </div>
                            <div>
                                <label for="contact_number" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('Contact number') }}</label>
                                <input id="contact_number" name="contact_number" type="tel" pattern="\+63[0-9]{10,12}" title="Enter a Philippine phone number starting with +63, for example +639171234567." class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-800 focus:ring-4 focus:ring-blue-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-900/60" placeholder="{{ __('+63...') }}" value="{{ old('contact_number') }}" required>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/70">
                            <input type="hidden" name="reporting_for_other" value="0">
                            <label class="flex items-center gap-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                <input id="reporting_for_other" name="reporting_for_other" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-blue-800 focus:ring-blue-800 dark:border-slate-600 dark:bg-slate-900" @checked(old('reporting_for_other')) />
                                {{ __('I am reporting for someone else') }}
                            </label>

                            <div id="other-person-fields" class="mt-4 space-y-4 @if(!old('reporting_for_other')) hidden @endif">
                                <div>
                                    <label for="relationship" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('Relationship to the person') }}</label>
                                    <input id="relationship" name="relationship" type="text" maxlength="80" title="Describe how you know the person you are reporting for." class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-800 focus:ring-4 focus:ring-blue-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-900/60" placeholder="{{ __('Parent / neighbor / coworker') }}" value="{{ old('relationship') }}">
                                </div>

                                <input type="hidden" name="victim_contactable" value="0">
                                <label class="flex items-center gap-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                    <input id="victim_contactable" name="victim_contactable" type="checkbox" value="1" class="h-4 w-4 rounded border-slate-300 text-blue-800 focus:ring-blue-800 dark:border-slate-600 dark:bg-slate-900" @checked(old('victim_contactable')) />
                                    {{ __('The person can be contacted directly') }}
                                </label>
                            </div>
                        </div>

                        @if(config('services.turnstile.site_key'))
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60">
                                <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
                            </div>
                        @endif
                    </fieldset>

                    <fieldset class="space-y-4 border-t border-slate-200 pt-4 dark:border-slate-700" aria-labelledby="location-heading">
                        <legend id="location-heading" class="text-lg font-black text-slate-900 dark:text-white">{{ __('Location') }}</legend>
                        
                        {{-- Map & Auto-Locate Button --}}
                        <div>
                            <div class="mb-3 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200">
                                    {{ __('Pin your exact location') }} <span class="font-normal text-slate-500">(optional)</span>
                                </label>
                                <button type="button" id="locate-btn" class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span id="locate-btn-text">{{ __('Use my current location') }}</span>
                                </button>
                            </div>
                            
                            <div id="map" class="border border-slate-300 dark:border-slate-600"></div>
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('Click the button to find yourself automatically, or click on the map to place the pin manually.') }}</p>
                        </div>

                        <div>
                            <label for="location_text" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('Location details / landmark') }}</label>
                            <input id="location_text" name="location_text" type="text" title="Enter a barangay, street, landmark, or nearby location. You can use GPS instead." class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-blue-800 focus:ring-4 focus:ring-blue-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-900/60" placeholder="{{ __('Barangay / landmark / street name') }}" value="{{ old('location_text') }}">
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('A nearby landmark helps responders find the location.') }}</p>
                        </div>
                        
                        <input id="latitude" name="latitude" type="hidden" value="{{ old('latitude') }}">
                        <input id="longitude" name="longitude" type="hidden" value="{{ old('longitude') }}">
                        <input id="location_accuracy" name="location_accuracy" type="hidden" value="{{ old('location_accuracy') }}">
                        
                        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-bold text-slate-900 dark:text-slate-100">{{ __('Add one supporting photo') }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Optional. JPG, JPEG, PNG, or WebP up to 4 MB. Avoid images that identify victims.') }}</p>
                                </div>
                            </div>
                            <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-4 block w-full text-sm text-slate-700 file:mr-4 file:min-h-10 file:border-0 file:bg-blue-800 file:px-4 file:py-2 file:font-bold file:text-white dark:text-slate-200 dark:file:bg-blue-400 dark:file:text-slate-950">
                        </div>
                    </fieldset>

                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-[4px] bg-blue-800 px-6 py-3 text-sm font-black text-white transition hover:bg-blue-900 dark:bg-blue-400 dark:text-slate-950 dark:hover:bg-blue-300">{{ __('Submit a report') }}</button>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Your information is only used to respond to this report.') }}</p>
                    </div>
                </form>
            </main>
        </div>
        @include('public.report-flow')
        @include('public.footer')
    </div>

    {{-- Leaflet JS --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Default coordinates (Kalibo, Aklan roughly)
            const defaultLat = 11.7061;
            const defaultLng = 122.3687;
            
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const accInput = document.getElementById('location_accuracy');
            const locationTextInput = document.getElementById('location_text');
            const locateBtn = document.getElementById('locate-btn');
            const locateBtnText = document.getElementById('locate-btn-text');

            // Track if the user types something manually
            let hasManuallyTyped = false;
            locationTextInput.addEventListener('input', () => {
                hasManuallyTyped = true;
            });

            // Check if there are old values from a failed validation
            const initialLat = latInput.value ? parseFloat(latInput.value) : defaultLat;
            const initialLng = lngInput.value ? parseFloat(lngInput.value) : defaultLng;

            // Initialize Map
            const map = L.map('map').setView([initialLat, initialLng], 13);

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Add a marker
            const marker = L.marker([initialLat, initialLng], {
                draggable: true
            }).addTo(map);

            // Function to update inputs and reverse geocode
            async function updateLocation(lat, lng, forceUpdate = false) {
                latInput.value = lat.toFixed(7);
                lngInput.value = lng.toFixed(7);

                // Update address if user hasn't manually typed OR if forceUpdate is true
                if (!hasManuallyTyped || forceUpdate) {
                    locationTextInput.placeholder = "Fetching address...";
                    try {
                        // Use OpenStreetMap Nominatim API (zoom=16 grabs streets better than 18)
                        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=16&addressdetails=1`);
                        const data = await response.json();
                        
                        if (data && data.address) {
                            const addr = data.address;
                            
                            // Extract specific components targeting PH address structures
                            const street = addr.road || addr.pedestrian || addr.path || '';
                            // In the PH, barangays can be under village, suburb, neighbourhood, or city_district
                            const barangay = addr.village || addr.suburb || addr.city_district || addr.neighbourhood || addr.quarter || '';
                            const municipality = addr.city || addr.town || addr.municipality || '';
                            
                            // Format cleanly: "Street, Brgy. Barangay, Municipality"
                            let formattedAddress = [];
                            
                            if (street) formattedAddress.push(street);
                            
                            // Add Brgy. prefix if it doesn't already start with "Brgy" or "Barangay"
                            if (barangay) {
                                if (barangay.toLowerCase().startsWith('brgy') || barangay.toLowerCase().startsWith('barangay')) {
                                    formattedAddress.push(barangay);
                                } else {
                                    formattedAddress.push(`Brgy. ${barangay}`);
                                }
                            }
                            
                            if (municipality) formattedAddress.push(municipality);

                            // If we got the main parts, use them. Otherwise, fallback to display_name.
                            if (formattedAddress.length > 0) {
                                locationTextInput.value = formattedAddress.join(', ');
                            } else {
                                locationTextInput.value = data.display_name;
                            }
                        }
                    } catch (error) {
                        console.log("Could not fetch address automatically.");
                    } finally {
                        locationTextInput.placeholder = "Barangay / landmark / street name";
                    }
                }
            }

            // Update hidden inputs when marker is dragged
            marker.on('dragend', function () {
                const position = marker.getLatLng();
                updateLocation(position.lat, position.lng, false); 
            });

            // Update marker and inputs when map is clicked
            map.on('click', function (e) {
                marker.setLatLng(e.latlng);
                updateLocation(e.latlng.lat, e.latlng.lng, false);
            });

            // Handle "Use my current location" button click
            locateBtn.addEventListener('click', function() {
                if (!navigator.geolocation) {
                    alert("Geolocation is not supported by your browser.");
                    return;
                }

                locateBtnText.textContent = "Locating...";
                locateBtn.disabled = true;

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const userLat = position.coords.latitude;
                        const userLng = position.coords.longitude;
                        
                        map.setView([userLat, userLng], 16);
                        marker.setLatLng([userLat, userLng]);
                        accInput.value = position.coords.accuracy.toFixed(2);
                        
                        // Reset manual typing since they clicked "use my location"
                        hasManuallyTyped = false;
                        locationTextInput.value = '';
                        
                        // Force the update
                        updateLocation(userLat, userLng, true);

                        locateBtnText.textContent = "Location Pinned";
                        locateBtn.disabled = false;
                        
                        setTimeout(() => {
                            locateBtnText.textContent = "Use my current location";
                        }, 3000);
                    },
                    (error) => {
                        console.log("Geolocation failed:", error.message);
                        alert("Unable to retrieve your location. Please ensure location services are enabled and permissions are granted.");
                        locateBtnText.textContent = "Use my current location";
                        locateBtn.disabled = false;
                    },
                    { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
                );
            });

            // Auto-trigger on load if empty
            if (!latInput.value && !lngInput.value && navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const userLat = position.coords.latitude;
                        const userLng = position.coords.longitude;
                        map.setView([userLat, userLng], 16);
                        marker.setLatLng([userLat, userLng]);
                        accInput.value = position.coords.accuracy.toFixed(2);
                        
                        hasManuallyTyped = false;
                        updateLocation(userLat, userLng, true);
                    },
                    (error) => { console.log("Auto-location denied."); }
                );
            }
        });
    </script>
</body>
</html>