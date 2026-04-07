//ES Module Class (Map.js)
import BaseMap from "./BaseMap";

export default class Map extends BaseMap {
    constructor({ el }) {
        super({ el });

        this.markers = {};

        this.loadedCells = new Set(); // cache

        this.cellSize = 0.01;

        this.visibleMarkers = new Set();

        this.isUpdatingVisibility = false;

        this.addQueue = [];
        this.isProcessingQueue = false;
    }

    init() {
        const { center, zoom } = this.getInitialView();

        this.initMap(center, zoom);

        this.bindEvents();

        this.loadMarkersForCells().then(() => {
            this.updateVisibleMarkers(); // 🔥 important
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
    async loadMarkersForCells() {
        const cells = this.getVisibleCells();

        // 🔥 find only NEW cells
        const newCells = cells.filter((cell) => !this.loadedCells.has(cell));

        if (newCells.length === 0) {
            return; // ✅ already cached → no request
        }

        // 🔥 mark as loaded BEFORE request (prevents duplicates)
        newCells.forEach((cell) => this.loadedCells.add(cell));

        const res = await fetch(`/api/map-points?cells=${newCells.join(",")}`);
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
        });

        marker.on("click", () => {
            Livewire.dispatch("locationSelected", {
                locationId: point.id,
            });
        });

        this.markers[key] = marker;

        const isVisible = this.map.getBounds().contains(marker.getLatLng());

        if (isVisible) {
            marker.addTo(this.map);
            this.visibleMarkers.add(key);
        }
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
                this.loadMarkersForCells();

                // 🔥 delay visibility update (breaks recursion loop)
                setTimeout(() => {
                    this.updateVisibleMarkers();
                }, 0);
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

    getVisibleCells() {
        const bounds = this.map.getBounds();

        const south = Math.floor(bounds.getSouth() / this.cellSize);
        const north = Math.floor(bounds.getNorth() / this.cellSize);
        const west = Math.floor(bounds.getWest() / this.cellSize);
        const east = Math.floor(bounds.getEast() / this.cellSize);

        const cells = [];

        for (let lat = south; lat <= north; lat++) {
            for (let lng = west; lng <= east; lng++) {
                cells.push(`${lat}:${lng}`);
            }
        }

        return cells;
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
                this.map.removeLayer(marker);
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
