const initReportLocation = () => {
    const mapElement = document.getElementById('map');
    if (!mapElement || typeof window.L === 'undefined') return;

    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const accuracyInput = document.getElementById('location_accuracy');
    const locationText = document.getElementById('location_text');
    const autoFilledNote = document.getElementById('location-autofilled-note');
    const locateBtn = document.getElementById('locate-btn');
    const locateLabel = document.getElementById('locate-btn-text');
    const locateStatus = document.getElementById('location-status');
    const pinState = document.getElementById('pin-state');
    const clearPin = document.getElementById('clear-pin');
    const liveRegion = document.getElementById('pin-live-region');

    const locale = document.documentElement.lang || 'en';
    const addressCache = new Map();
    let dragTimer;
    let marker;

    const setStatus = (state, message) => {
        if (!locateStatus) return;
        locateStatus.dataset.state = state;
        locateStatus.textContent = message;
    };

    const announce = (message) => {
        if (liveRegion) liveRegion.textContent = '';
        window.requestAnimationFrame(() => { if (liveRegion) liveRegion.textContent = message; });
    };

    const rounded = (value) => Number(value).toFixed(4);
    const formatAddress = (address) => {
        if (!address) return '';
        const street = address.road || address.pedestrian || address.path || '';
        const barangay = address.village || address.suburb || address.city_district || address.neighbourhood || address.quarter || '';
        const municipality = address.city || address.town || address.municipality || '';
        const parts = [];
        if (street) parts.push(street);
        if (barangay) parts.push(/^brgy\.?|^barangay/i.test(barangay) ? barangay : `Brgy. ${barangay}`);
        if (municipality) parts.push(municipality);
        return parts.join(', ');
    };

    const reverseGeocode = async (lat, lng) => {
        const key = `${rounded(lat)},${rounded(lng)}`;
        if (addressCache.has(key)) {
            if (!locationText.value.trim()) {
                locationText.value = addressCache.get(key);
                autoFilledNote?.classList.remove('hidden');
            }
            return;
        }

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&zoom=16&addressdetails=1`, {
                headers: { 'Accept-Language': locale },
            });
            if (!response.ok) return;
            const data = await response.json();
            const address = formatAddress(data.address) || data.display_name || '';
            if (!address) return;
            addressCache.set(key, address);
            if (!locationText.value.trim()) {
                locationText.value = address;
                autoFilledNote?.classList.remove('hidden');
            }
        } catch {
            // Reverse geocoding is optional; retain the editable location field.
        }
    };

    const updatePin = (lat, lng, accuracy = null, source = 'pin') => {
        latInput.value = Number(lat).toFixed(7);
        lngInput.value = Number(lng).toFixed(7);
        accuracyInput.value = accuracy == null ? '' : Number(accuracy).toFixed(2);
        marker?.setLatLng([lat, lng]);

        const accuracyText = accuracy == null ? '' : ` · ±${Math.round(accuracy)} m`;
        if (pinState) {
            pinState.innerHTML = `📍 Pin set (${Number(lat).toFixed(4)}, ${Number(lng).toFixed(4)})${accuracyText} <button type="button" id="clear-pin" class="font-bold underline underline-offset-2">Clear pin</button>`;
            pinState.dataset.set = 'true';
            pinState.querySelector('#clear-pin')?.addEventListener('click', clearPinState);
        }
        announce(`Pin set at ${Number(lat).toFixed(4)}, ${Number(lng).toFixed(4)}${accuracyText}`);

        if (source === 'geolocation') reverseGeocode(lat, lng);
    };

    const clearPinState = () => {
        latInput.value = '';
        lngInput.value = '';
        accuracyInput.value = '';
        if (marker) mapElement.classList.contains('leaflet-container') && marker.setLatLng([11.7061, 122.3687]);
        if (pinState) {
            pinState.textContent = 'No pin set — responders will rely on the landmark text.';
            pinState.dataset.set = 'false';
        }
        announce('Pin cleared.');
        setStatus('idle', '');
    };

    const initialLat = latInput.value ? Number(latInput.value) : 11.7061;
    const initialLng = lngInput.value ? Number(lngInput.value) : 122.3687;
    const map = window.L.map(mapElement, { scrollWheelZoom: false }).setView([initialLat, initialLng], latInput.value && lngInput.value ? 16 : 13);
    const tileUrl = mapElement.dataset.tileUrl || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    const tileKey = mapElement.dataset.tileKey || '';
    window.L.tileLayer(tileUrl.replace('{key}', encodeURIComponent(tileKey)), {
        maxZoom: 19,
        attribution: mapElement.dataset.tileAttribution || '&copy; OpenStreetMap contributors',
    }).addTo(map);
    marker = window.L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

    mapElement.setAttribute('role', 'application');
    mapElement.setAttribute('aria-label', 'Incident location map. Click or drag the pin to set coordinates.');

    map.on('click', (event) => {
        clearTimeout(dragTimer);
        updatePin(event.latlng.lat, event.latlng.lng);
    });

    marker.on('dragend', () => {
        clearTimeout(dragTimer);
        dragTimer = window.setTimeout(() => {
            const position = marker.getLatLng();
            updatePin(position.lat, position.lng);
        }, 400);
    });

    locationText?.addEventListener('input', () => {
        autoFilledNote?.classList.add('hidden');
    });

    clearPin?.addEventListener('click', clearPinState);

    locateBtn?.addEventListener('click', () => {
        if (!navigator.geolocation) {
            setStatus('error', 'Location unavailable — please check permissions or drop the pin manually.');
            return;
        }

        locateBtn.setAttribute('aria-busy', 'true');
        locateBtn.disabled = true;
        locateLabel.innerHTML = '<span class="feedback-spinner inline-block align-middle" aria-hidden="true"></span> Locating…';
        setStatus('locating', 'Locating…');

        navigator.geolocation.getCurrentPosition((position) => {
            const { latitude, longitude, accuracy } = position.coords;
            map.setView([latitude, longitude], 16);
            updatePin(latitude, longitude, accuracy, 'geolocation');
            locateBtn.removeAttribute('aria-busy');
            locateBtn.disabled = false;
            locateLabel.textContent = 'Location pinned';
            setStatus('success', 'Location pinned');
            window.setTimeout(() => {
                locateLabel.textContent = 'Use my current location';
                setStatus('idle', '');
            }, 3000);
        }, () => {
            locateBtn.removeAttribute('aria-busy');
            locateBtn.disabled = false;
            locateLabel.textContent = 'Use my current location';
            setStatus('error', 'Location unavailable — please check permissions or drop the pin manually.');
        }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 });
    });

    if (latInput.value && lngInput.value) {
        updatePin(initialLat, initialLng, accuracyInput.value || null);
    } else {
        clearPinState();
    }
};

document.addEventListener('DOMContentLoaded', initReportLocation);
