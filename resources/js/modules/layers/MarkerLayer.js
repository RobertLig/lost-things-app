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

        this.filters = {};

        this.requestId = 0;

        this.isAutoFitting = false;
    }

    clearMarkers() {
        Object.values(this.markers).forEach((marker) => {
            if (this.map.hasLayer(marker)) {
                this.map.removeLayer(marker);
            }
        });

        this.markers = {};

        this.visibleMarkers = new Set();

        // 🔥 CRITICAL FIX
        this.addQueue = [];
        this.isProcessingQueue = false;

        console.log("markers after clear", Object.keys(this.markers).length);
    }

    setFilters(filters) {
        console.log("setFilters called", filters);

        this.filters = filters;
        this.lastBbox = null; // 🔥 force reload
        this.loadMarkers();
    }

    onAdd() {
        this.loadMarkers();
    }

    async loadMarkers() {
        const currentRequestId = ++this.requestId;

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

        const data = await this.service.fetchPoints({
            bbox,
            filters: this.filters,
        });

        // 🔥 IGNORE outdated responses
        if (currentRequestId !== this.requestId) {
            return;
        }

        // 🔥 CLEAR before adding new ones
        this.clearMarkers();

        const points = data.points;
        const boundsData = data.bounds;

        console.log("points from API", points);

        points.forEach((point) => {
            const key = point.id;

            if (this.markers[key]) return;

            this.add(point);
        });

        this.updateVisibility(bounds);

        this.handleAutoFit(boundsData, points);
    }

    handleAutoFit(boundsData, points) {
        console.log("AUTO FIT boundsData", boundsData);

        if (!points.length || !boundsData) return;

        const currentBounds = this.map.getBounds();

        const hasVisible = points.some((point) =>
            currentBounds.contains([Number(point.lat), Number(point.lng)]),
        );

        if (!hasVisible) {
            this.isAutoFitting = true; // 🚨 LOCK

            const leafletBounds = L.latLngBounds(
                [Number(boundsData.south), Number(boundsData.west)],
                [Number(boundsData.north), Number(boundsData.east)],
            );

            this.map.fitBounds(leafletBounds, {
                padding: [50, 50],
                maxZoom: 14,
            });

            this.map.once("moveend", () => {
                this.isAutoFitting = false; // 🔓 UNLOCK
            });
        }
    }

    onMove(bounds) {
        if (this.isAutoFitting) return; // 🚫 ignore auto-fit movement

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
            // 🔥 highlight immediately
            this.highlight(point.id);

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

        // 🔒 prevent re-trigger on same marker
        if (this.activeEl === el) {
            // 🔁 re-trigger pulse only (no flyTo, no state change)
            el.classList.remove("marker-pulse");

            // force reflow so animation restarts
            void el.offsetWidth;

            el.classList.add("marker-pulse");

            return;
        }

        const prevEl = this.activeEl;

        // 🛑 cancel previous pending moveend handler (fast clicks protection)
        if (this.moveHandler) {
            this.map.off("moveend", this.moveHandler);
            this.moveHandler = null;
        }

        // 🗺 move map
        this.map.flyTo(marker.getLatLng(), 15, {
            duration: 0.8,
        });

        // 🎬 define handler
        this.moveHandler = () => {
            // ✅ activate new marker FIRST
            el.classList.add("marker-active");
            el.classList.add("marker-pulse");

            this.activeEl = el;

            // ✅ remove previous AFTER new is active (no flicker)
            if (prevEl && prevEl !== el) {
                prevEl.classList.remove("marker-active");
            }

            // ✨ remove pulse only (keep active)
            setTimeout(() => {
                el.classList.remove("marker-pulse");
            }, 1200);

            this.moveHandler = null;
        };

        // 🎯 run once after movement
        this.map.once("moveend", this.moveHandler);
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
