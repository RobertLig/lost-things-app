//ES Module Class (Map.js)
import BaseMap from "./BaseMap";

export default class Map extends BaseMap {
    constructor({ el }) {
        super({ el });

        this.markers = {};

        this.visibleMarkers = new Set();

        this.isUpdatingVisibility = false;

        this.addQueue = [];
        this.isProcessingQueue = false;
    }

    init() {
        const { center, zoom } = this.getInitialView();

        this.initMap(center, zoom);

        this.bindEvents();

        this.loadMarkers();

        this.lastBbox = null;

        // 🔥 small delay so first markers exist
        setTimeout(() => {
            this.updateVisibleMarkers();
            this.handleInitialHighlight();
        }, 100);

        console.log("markers:", Object.keys(this.markers).length);
        console.log("visible:", this.visibleMarkers.size);
    }

    getInitialView() {
        return {
            center: [50.2649, 19.0238],
            zoom: 13,
        };
    }

    // 🔹 LOAD MARKERS
    async loadMarkers() {
        const bounds = this.map.getBounds();

        const padBounds = bounds.pad(0.2); // 20%

        const bbox = [
            padBounds.getWest(),
            padBounds.getSouth(),
            padBounds.getEast(),
            padBounds.getNorth(),
        ].join(",");

        if (this.lastBbox === bbox) return;
        this.lastBbox = bbox;

        const res = await fetch(`/api/map-points?bbox=${bbox}`);
        const points = await res.json();

        console.log("points:", points);

        points.forEach((point) => {
            const key = this.getKey(point);

            if (this.markers[key]) return;

            this.addMarker(point);
        });

        // 🔥 ensure markers appear
        this.updateVisibleMarkers();
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
        });

        marker.on("click", () => {
            Livewire.dispatch("locationSelected", {
                locationId: point.id,
            });
        });

        this.markers[key] = marker;

        this.updateVisibleMarkers();
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
                this.loadMarkers();
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

    updateVisibleMarkers() {
        if (this.isUpdatingVisibility) return; // 🔒 prevent recursion
        this.isUpdatingVisibility = true;

        const bounds = this.map.getBounds();

        Object.entries(this.markers).forEach(([key, marker]) => {
            const latLng = marker.getLatLng();
            const isVisible = bounds.contains(latLng);
            const isCurrentlyVisible = this.visibleMarkers.has(key);

            if (isVisible && !isCurrentlyVisible) {
                this.queueMarkerAdd(key, marker);
            }

            if (!isVisible && isCurrentlyVisible) {
                if (this.map.hasLayer(marker)) {
                    this.map.removeLayer(marker);
                }
                this.visibleMarkers.delete(key);
            }
        });

        this.isUpdatingVisibility = false;
    }

    queueMarkerAdd(key, marker) {
        this.addQueue.push({ key, marker });

        if (!this.isProcessingQueue) {
            this.processQueue();
        }
    }

    processQueue() {
        if (this.addQueue.length === 0) {
            this.isProcessingQueue = false;
            return;
        }

        this.isProcessingQueue = true;

        const { key, marker } = this.addQueue.shift();

        marker.addTo(this.map);
        this.visibleMarkers.add(key);

        setTimeout(() => this.processQueue(), 80); // 👈 same delay as before
    }
}
