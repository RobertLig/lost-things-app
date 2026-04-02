let map;
let markers = {};

export function initMainMap() {
    const el = document.getElementById("map");
    if (!el) return;

    // cleanup
    if (map) {
        map.remove();
        map = null;
    }

    markers = {};

    let center = [50.2649, 19.0238];
    let zoom = 13;

    if (window.newItem) {
        center = [window.newItem.lat, window.newItem.lng];
        zoom = 15;
    }

    map = L.map(el).setView(center, zoom);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);

    loadMarkers(() => {
        highlightNewItem();
    });
}

function loadMarkers(callback = null) {
    fetch("/api/map-points")
        .then((res) => res.json())
        .then((points) => {
            const baseDelay = window.newItem ? 300 : 100;

            points.forEach((point, index) => {
                setTimeout(
                    () => {
                        addOrUpdateMarker(point.lat, point.lng, point.count);
                    },
                    baseDelay + index * 120,
                );
            });

            setTimeout(
                () => {
                    if (callback) callback();
                },
                baseDelay + points.length * 120,
            );
        });
}

function addOrUpdateMarker(lat, lng, count = 1) {
    const key = `${lat},${lng}`;

    if (markers[key]) {
        const badge = markers[key].getElement().querySelector(".marker-badge");
        badge.innerText = parseInt(badge.innerText) + 1;
        return;
    }

    const marker = L.marker([lat, lng], {
        icon: L.divIcon({
            className: "",
            html: `
                <div class="marker">
                    <div class="marker-pin"></div>
                    <div class="marker-badge">${count}</div>
                </div>
            `,
            iconSize: [30, 42],
            iconAnchor: [15, 42],
        }),
    }).addTo(map);

    markers[key] = marker;

    map.panTo([lat, lng]);

    // ✅ click handler
    marker.on("click", () => {
        if (window.Livewire) {
            Livewire.dispatch("locationSelected", {
                lat,
                lng,
            });
        }
    });
}

function highlightNewItem() {
    if (!window.newItem) return;

    const { lat, lng } = window.newItem;
    const key = `${lat},${lng}`;

    setTimeout(() => {
        if (markers[key]) {
            const el = markers[key].getElement();

            el.classList.add("marker-highlight");

            setTimeout(() => {
                el.classList.remove("marker-highlight");
            }, 3500);
        }
    }, 300);
}

//picker map
export function initPickerMap() {
    const el = document.getElementById("picker-map");
    if (!el) return;

    if (el._map) return;

    const componentEl = el.closest("[wire\\:id]");
    if (!componentEl) return;

    const component = Livewire.find(componentEl.getAttribute("wire:id"));

    let map = L.map(el).setView([50.2649, 19.0238], 13);

    el._map = map;
    el._marker = null;

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);

    function updateMarker(lat, lng) {
        if (el._marker) el._marker.remove();

        el._marker = L.marker([lat, lng], {
            draggable: true,
        }).addTo(map);

        map.setView([lat, lng], 13);

        el._marker.on("dragend", function (e) {
            const pos = e.target.getLatLng();
            component.set("lat", pos.lat);
            component.set("lng", pos.lng);
        });
    }

    if (component.get("lat") && component.get("lng")) {
        updateMarker(component.get("lat"), component.get("lng"));
    }

    map.on("click", function (e) {
        const { lat, lng } = e.latlng;

        updateMarker(lat, lng);

        component.set("lat", lat);
        component.set("lng", lng);
    });

    component.$watch("lat", (lat) => {
        const lng = component.get("lng");
        if (lat && lng) updateMarker(lat, lng);
    });

    component.$watch("lng", (lng) => {
        const lat = component.get("lat");
        if (lat && lng) updateMarker(lat, lng);
    });
}

//useMyLocation
export function useMyLocation() {
    if (!navigator.geolocation) {
        alert("Geolocation is not supported by your browser");
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            const el = document.getElementById("picker-map");
            if (!el || !el._map) return;

            const map = el._map;

            const componentEl = el.closest("[wire\\:id]");
            if (!componentEl) return;

            const component = Livewire.find(
                componentEl.getAttribute("wire:id"),
            );

            if (el._marker) el._marker.remove();

            el._marker = L.marker([lat, lng], {
                draggable: true,
            }).addTo(map);

            map.setView([lat, lng], 15);

            component.set("lat", lat);
            component.set("lng", lng);

            el._marker.on("dragend", function (e) {
                const pos = e.target.getLatLng();
                component.set("lat", pos.lat);
                component.set("lng", pos.lng);
            });
        },
        (error) => {
            console.error(error);
        },
    );
}
