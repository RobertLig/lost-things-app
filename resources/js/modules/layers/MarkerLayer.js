import BaseLayer from "./BaseLayer";

export default class MarkerLayer extends BaseLayer {
    constructor(map, service) {
        super(map);

        this.service = service;

        this.markers = {};
        this.visibleMarkers = new Set();

        this.addQueue = [];
        this.isProcessingQueue = false;

        this.lastBbox = null;
    }

    onAdd() {
        this.loadMarkers();
    }

    async loadMarkers() {
        const bounds = this.map.getBounds();
        const padBounds = bounds.pad(0.2);

        const bbox = [
            padBounds.getWest(),
            padBounds.getSouth(),
            padBounds.getEast(),
            padBounds.getNorth(),
        ].join(",");

        if (this.lastBbox === bbox) return;
        this.lastBbox = bbox;

        const points = await this.service.fetchPoints(bbox);

        points.forEach((point) => {
            const key = point.id;

            if (this.markers[key]) return;

            this.add(point);
        });

        this.updateVisibility(bounds);
    }

    onMove(bounds) {
        this.loadMarkers();
        this.updateVisibility(bounds);
    }

    add(point) {
        const key = point.id;

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
    }

    updateVisibility(bounds) {
        Object.entries(this.markers).forEach(([key, marker]) => {
            const latLng = marker.getLatLng();
            const isVisible = bounds.contains(latLng);
            const isCurrentlyVisible = this.visibleMarkers.has(key);

            if (isVisible && !isCurrentlyVisible) {
                this.queueAdd(key, marker);
            }

            if (!isVisible && isCurrentlyVisible) {
                if (this.map.hasLayer(marker)) {
                    this.map.removeLayer(marker);
                }
                this.visibleMarkers.delete(key);
            }
        });
    }

    queueAdd(key, marker) {
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

        setTimeout(() => this.processQueue(), 80);
    }

    highlight(id) {
        const marker = this.markers[id];
        if (!marker) return;

        const el = marker.getElement();
        if (!el) return;

        el.classList.add("marker-highlight");

        this.map.flyTo(marker.getLatLng(), 15, {
            duration: 0.8,
        });

        setTimeout(() => {
            el.classList.remove("marker-highlight");
        }, 2000);
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
        const el = marker.getElement();
        if (!el) return;

        const badge = el.querySelector(".marker-badge");
        if (!badge) return;

        badge.innerText = parseInt(badge.innerText) + 1;
    }
}
