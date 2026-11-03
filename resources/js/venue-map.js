import L from 'leaflet';

const mapStates = new WeakMap();

function pinIcon(element) {
    const iconUrl = element?.dataset.markerIcon;

    if (!iconUrl) {
        return undefined;
    }

    return L.icon({
        iconUrl,
        iconSize: [36, 48],
        iconAnchor: [18, 46],
        popupAnchor: [0, -40],
        className: 'oshilog-map-pin',
    });
}

function addMarker(map, latLng, element, name) {
    const marker = L.marker(latLng, { icon: pinIcon(element) }).addTo(map);

    if (name) {
        marker.bindPopup(name);
    }

    return marker;
}

function initVenueMap(element) {
    if (!element || mapStates.has(element)) {
        return mapStates.get(element);
    }

    const latitude = parseFloat(element.dataset.lat);
    const longitude = parseFloat(element.dataset.lng);
    const name = element.dataset.name ?? '';

    const map = L.map(element);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    let marker = null;

    if (!Number.isNaN(latitude) && !Number.isNaN(longitude)) {
        map.setView([latitude, longitude], 15);
        marker = addMarker(map, [latitude, longitude], element, name);
    } else {
        map.setView([35.6812, 139.7671], 5);
    }

    const state = { map, marker };
    mapStates.set(element, state);

    [0, 100, 300].forEach((delay) => {
        setTimeout(() => map.invalidateSize(), delay);
    });

    return state;
}

function updateVenueMap(element, latitude, longitude, name = '') {
    if (!element) {
        return;
    }

    let state = mapStates.get(element);

    if (!state) {
        initVenueMap(element);
        state = mapStates.get(element);
    }

    const { map } = state;
    const latLng = [latitude, longitude];

    if (state.marker) {
        state.marker.setLatLng(latLng);
    } else {
        state.marker = addMarker(map, latLng, element, name);
    }

    if (name) {
        state.marker.bindPopup(name);
    }

    map.setView(latLng, 15);

    element.dataset.lat = String(latitude);
    element.dataset.lng = String(longitude);

    if (name) {
        element.dataset.name = name;
    }

    setTimeout(() => map.invalidateSize(), 100);
}

function initAllVenueMaps() {
    document.querySelectorAll('[data-venue-map]').forEach(initVenueMap);
}

function refreshVenueMaps() {
    initAllVenueMaps();

    document.querySelectorAll('[data-venue-map]').forEach((element) => {
        mapStates.get(element)?.map.invalidateSize();
    });
}

function scheduleRefreshVenueMaps() {
    requestAnimationFrame(() => {
        refreshVenueMaps();
        setTimeout(refreshVenueMaps, 150);
    });
}

function elementHasVenueMap(element) {
    return element?.nodeType === 1
        && (element.matches?.('[data-venue-map]') || Boolean(element.querySelector?.('[data-venue-map]')));
}

document.addEventListener('DOMContentLoaded', initAllVenueMaps);
document.addEventListener('livewire:navigated', scheduleRefreshVenueMaps);

document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.added', ({ el }) => {
        if (elementHasVenueMap(el)) {
            scheduleRefreshVenueMaps();
        }
    });

    Livewire.hook('commit', ({ succeed }) => {
        succeed(() => scheduleRefreshVenueMaps());
    });

    Livewire.on('venue-map-updated', ({ lat, lng, name, id }) => {
        const element = document.getElementById(id);

        if (element) {
            updateVenueMap(element, lat, lng, name ?? '');
        }
    });
});

export { initVenueMap, initAllVenueMaps, updateVenueMap };
