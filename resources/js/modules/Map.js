//ES Module Class (Map.js)
export default class Map {
    constructor({ el, newItem = null }) {
        this.el = el;
        this.newItem = newItem;

        this.map = null;
        this.markers = {};
    }

    // 🔹 INIT
    init() {
        if (!this.el) return;

        this.destroy();

        const { center, zoom } = this.getInitialView();

        this.map = L.map(this.el).setView(center, zoom);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap contributors",
        }).addTo(this.map);

        this.bindEvents();
        this.loadMarkers(() => this.highlightNewItem());
    }

    // 🔹 CLEANUP
    destroy() {
        if (this.map) {
            this.map.remove();
            this.map = null;
        }

        this.markers = {};
    }

    // 🔹 INITIAL VIEW
    getInitialView() {
        if (this.newItem) {
            return {
                center: [this.newItem.lat, this.newItem.lng],
                zoom: 12, //15
            };
        }

        return {
            center: [50.2649, 19.0238],
            zoom: 13,
        };
    }

    // 🔹 LOAD MARKERS
    async loadMarkers(callback = null) {
        const res = await fetch("/api/map-points");
        const points = await res.json();

        const baseDelay = this.newItem ? 300 : 100;

        points.forEach((point, index) => {
            setTimeout(
                () => {
                    this.addMarker(point);
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
        this.map.panTo(marker.getLatLng());

        setTimeout(() => {
            el.classList.remove("marker-highlight");
        }, 2000);
    }

    highlightNewItem() {
        if (!this.newItem) return;

        setTimeout(() => {
            this.highlightMarkerById(this.newItem.lat, this.newItem.lng);
        }, 300);
    }

    // 🔹 EVENTS (Livewire ↔ JS)
    bindEvents() {
        // Livewire → JS (browser event)
        window.addEventListener("highlightMapMarker", (e) => {
            this.highlightMarkerById(e.detail.locationId);
        });
    }
}
