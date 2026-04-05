import BaseMap from "./BaseMap";

export default class PickerMap extends BaseMap {
    constructor({ el, component }) {
        super({ el });

        this.component = component;
        this.marker = null;
    }

    init() {
        this.initMap();

        this.bindMapEvents();
        this.bindLivewire();

        this.initFromComponent();
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
            draggable: true,
        }).addTo(this.map);

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
