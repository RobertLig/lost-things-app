// resources/js/modules/BaseMap.js

export default class BaseMap {
    constructor({ el }) {
        this.el = el;
        this.map = null;
    }

    // 🔹 INIT MAP
    initMap(center = [50.2649, 19.0238], zoom = 13) {
        if (!this.el) return;

        // 🔥 Firefox fix
        L.Browser.pointer = true;
        L.Browser.touch = false;

        if (this.map) {
            this.map.remove();
            this.map = null;
        }

        this.map = L.map(this.el).setView(center, zoom);

        this.map.tap = false;

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
        if (!this.map) return;

        // 🔥 remove all handlers (critical)
        this.map.off();

        // 🔥 remove DOM + listeners
        this.map.remove();

        this.map = null;
    }
}
