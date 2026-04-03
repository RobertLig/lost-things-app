//ES module class

export default class PickerMap {
    constructor({ el, component }) {
        this.el = el;
        this.component = component;

        this.map = null;
        this.marker = null;
    }

    // 🔹 INIT
    init() {
        if (!this.el || this.map) return;

        this.map = L.map(this.el).setView([50.2649, 19.0238], 13);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap contributors",
        }).addTo(this.map);

        this.bindMapEvents();
        this.bindLivewire();

        this.initFromComponent();
    }

    // 🔹 INITIAL STATE
    initFromComponent() {
        const lat = this.component.get("lat");
        const lng = this.component.get("lng");

        if (lat && lng) {
            this.updateMarker(lat, lng);
        }
    }

    // 🔹 MARKER LOGIC
    updateMarker(lat, lng) {
        if (this.marker) {
            this.marker.remove();
        }

        this.marker = L.marker([lat, lng], {
            draggable: true,
        }).addTo(this.map);

        this.map.setView([lat, lng], 13);

        this.marker.on("dragend", (e) => {
            const pos = e.target.getLatLng();
            this.updateComponent(pos.lat, pos.lng);
        });
    }

    // 🔹 MAP EVENTS
    bindMapEvents() {
        this.map.on("click", (e) => {
            const { lat, lng } = e.latlng;

            this.updateMarker(lat, lng);
            this.updateComponent(lat, lng);
        });
    }

    // 🔹 LIVEWIRE SYNC
    bindLivewire() {
        this.component.$watch("lat", (lat) => {
            const lng = this.component.get("lng");
            if (lat && lng) this.updateMarker(lat, lng);
        });

        this.component.$watch("lng", (lng) => {
            const lat = this.component.get("lat");
            if (lat && lng) this.updateMarker(lat, lng);
        });
    }

    updateComponent(lat, lng) {
        if (
            this.component.get("lat") === lat &&
            this.component.get("lng") === lng
        )
            return;

        this.component.set("lat", lat);
        this.component.set("lng", lng);
    }

    // 🔹 CLEANUP (optional but good)
    destroy() {
        if (this.map) {
            this.map.remove();
            this.map = null;
            this.marker = null;
        }
    }
}
