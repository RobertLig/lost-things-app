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
