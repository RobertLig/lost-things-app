//ES Module Class (Map.js)
import BaseMap from "./BaseMap";

export default class Map extends BaseMap {
    constructor({ el }) {
        super({ el });

        this.markers = {};
    }

    init() {
        const { center, zoom } = this.getInitialView();

        this.initMap(center, zoom);

        this.bindEvents();

        this.loadMarkersForBounds().then(() => {
            this.handleInitialHighlight();
        });
    }

    getInitialView() {
        return {
            center: [50.2649, 19.0238],
            zoom: 13,
        };
    }

    // 🔹 LOAD MARKERS
    async loadMarkersForBounds() {
        const bounds = this.map.getBounds();

        const query = [
            bounds.getSouth(),
            bounds.getWest(),
            bounds.getNorth(),
            bounds.getEast(),
        ].join(",");

        const res = await fetch(`/api/map-points?bounds=${query}`);
        const points = await res.json();

        const delay = 80;

        return new Promise((resolve) => {
            points.forEach((point, index) => {
                const key = this.getKey(point);

                // 🔥 skip already loaded markers
                if (this.markers[key]) return;

                setTimeout(() => {
                    this.addMarker(point);

                    if (index === points.length - 1) {
                        resolve();
                    }
                }, index * delay);
            });

            if (points.length === 0) {
                resolve();
            }
        });
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
        let timeout = null;

        this.map.on("moveend", () => {
            clearTimeout(timeout);

            timeout = setTimeout(() => {
                this.loadMarkersForBounds();
            }, 200);
        });

        window.addEventListener("highlightMapMarker", (e) => {
            const { locationId } = e.detail;
            this.highlightMarkerById(locationId);
        });
    }

    handleInitialHighlight() {
        const locationId = this.el.dataset.highlightLocationId;

        if (!locationId) return;

        this.highlightMarkerById(parseInt(locationId));

        // 🔥 one-time use
        delete this.el.dataset.highlightLocationId;
    }
}
