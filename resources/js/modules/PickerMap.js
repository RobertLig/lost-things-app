import BaseMap from "./BaseMap";
import { createMarkerIcon } from "./MarkerFactory";

export default class PickerMap extends BaseMap {
    constructor({ el, component }) {
        super({ el });

        this.component = component;
        this.marker = null;
    }

    init() {
        this.initMap();

        // 🔥 wait for DOM + layout + paint to finish
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                // 🔥 CRITICAL: force full recalculation
                this.map.invalidateSize(true);

                const center = this.map.getCenter();

                // 🔥 THIS LINE FIXES THE OFFSET BUG
                this.map.setView(center, this.map.getZoom(), {
                    animate: false,
                    reset: true,
                });
            });
        });

        this.bindMapEvents();
        this.bindLivewire();
        this.initFromComponent();
    }

    waitForStableLayout() {
        return new Promise((resolve) => {
            let last = null;
            let stableCount = 0;

            const check = () => {
                const rect = this.el.getBoundingClientRect();

                const current = `${rect.top}-${rect.left}-${rect.width}-${rect.height}`;

                if (current === last) {
                    stableCount++;
                } else {
                    stableCount = 0;
                    last = current;
                }

                // 🔥 wait for 3 stable frames
                if (stableCount >= 3) {
                    resolve();
                } else {
                    requestAnimationFrame(check);
                }
            };

            check();
        });
    }

    initFromComponent() {
        const lat = this.component.get("lat");
        const lng = this.component.get("lng");

        if (lat && lng) {
            this.updateMarker(lat, lng);
        }
    }

    updateMarker(lat, lng) {
        if (this.marker) {
            this.marker.remove();
        }

        this.marker = L.marker([lat, lng], {
            icon: createMarkerIcon(), // ✅ same marker
            draggable: true,
        }).addTo(this.map);

        // 🔥 reset marker drag state too
        if (this.marker.dragging && this.marker.dragging._draggable) {
            const d = this.marker.dragging._draggable;
            d._moved = false;
            d._moving = false;
            d._startPoint = null;
            d._startPos = null;
        }

        // 🔥 FORCE correct dragging behavior
        this.marker.dragging.enable();
        this.map.dragging.enable();

        this.setView(lat, lng, 13);

        this.marker.on("dragend", (e) => {
            const pos = e.target.getLatLng();

            this.updateComponent(pos.lat, pos.lng);
        });
    }

    bindMapEvents() {
        this.map.on("click", (e) => {
            const { lat, lng } = e.latlng;

            this.updateMarker(lat, lng);
            this.updateComponent(lat, lng);
        });
    }

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

    // 🔹 GEOLOCATION (now cleanly inside class)
    useMyLocation() {
        if (!navigator.geolocation) {
            alert("Geolocation is not supported by your browser");
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                this.updateMarker(lat, lng);
                this.updateComponent(lat, lng);
            },
            (error) => {
                console.error(error);
                alert("Unable to retrieve your location");
            },
        );
    }
}
