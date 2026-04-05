//ES Module Class (Map.js)
import BaseMap from "./BaseMap";

export default class Map extends BaseMap {
    constructor({ el }) {
        super({ el });

        this.markers = {};

        // 🔥 Promise that resolves when markers are ready
        this.markersLoaded = new Promise((resolve) => {
            this._resolveMarkersLoaded = resolve;
        });
    }

    init() {
        const { center, zoom } = this.getInitialView();

        this.initMap(center, zoom);

        this.bindEvents(); // 🔥 IMPORTANT

        this.loadMarkers();
    }

    getInitialView() {
        return {
            center: [50.2649, 19.0238],
            zoom: 13,
        };
    }

    // ... (rest stays the same, but replace map.* with this.map)
    // 🔹 LOAD MARKERS
    async loadMarkers() {
        const res = await fetch("/api/map-points");
        const points = await res.json();

        const delay = 80; // tweak for speed (lower = faster animation)

        points.forEach((point, index) => {
            setTimeout(() => {
                this.addMarker(point);

                // 🔥 resolve ONLY after last marker
                if (index === points.length - 1) {
                    this._resolveMarkersLoaded();
                }
            }, index * delay);
        });

        // ⚠️ edge case: no points
        if (points.length === 0) {
            this._resolveMarkersLoaded();
        }
    }

    // 🔹 ADD MARKER
    addMarker(point) {
        const key = this.getKey(point);

        if (this.markers[key]) {
            this.incrementBadge(this.markers[key]);
            return;
        }

        const marker = L.marker([point.lat, point.lng], {
            icon: this.createIcon(point.count),
        }).addTo(this.map);

        marker.on("click", () => {
            Livewire.dispatch("locationSelected", {
                locationId: point.id,
            });
        });

        this.markers[key] = marker;
    }

    createIcon(count) {
        return L.divIcon({
            className: "",
            html: `
                <div class="marker">
                    <div class="marker-pin"></div>
                    <div class="marker-badge">${count}</div>
                </div>
            `,
            iconSize: [30, 42],
            iconAnchor: [15, 42],
        });
    }

    incrementBadge(marker) {
        const badge = marker.getElement().querySelector(".marker-badge");
        badge.innerText = parseInt(badge.innerText) + 1;
    }

    getKey(point) {
        return point.id;
    }

    // 🔹 HIGHLIGHT
    highlightMarkerById(locationId) {
        const marker = this.markers[locationId];
        if (!marker) return;

        const el = marker.getElement();

        el.classList.add("marker-highlight");

        this.map.flyTo(marker.getLatLng(), 15, {
            duration: 0.8,
        });

        setTimeout(() => {
            el.classList.remove("marker-highlight");
        }, 2000);
    }

    // 🔹 EVENTS (Livewire ↔ JS)
    bindEvents() {
        window.addEventListener("highlightMapMarker", async (e) => {
            const { locationId } = e.detail;

            // 🔥 wait until markers exist
            await this.markersLoaded;

            this.highlightMarkerById(locationId);
        });
    }
}
