// resources/js/modules/BaseMap.js

export default class BaseMap {
    constructor({ el }) {
        this.el = el;
        this.map = null;
    }

    // 🔹 INIT MAP
    initMap(center = [50.2649, 19.0238], zoom = 13) {
        if (!this.el || this.map) return;

        this.map = L.map(this.el).setView(center, zoom);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap contributors",
        }).addTo(this.map);
    }

    // 🔹 SET VIEW
    setView(lat, lng, zoom = 13) {
        if (!this.map) return;
        this.map.setView([lat, lng], zoom);
    }

    // 🔹 DESTROY
    destroy() {
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
    }
}
