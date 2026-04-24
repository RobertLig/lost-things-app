// resources/js/modules/ShowMap.js
import BaseMap from "./BaseMap";

export default class ShowMap extends BaseMap {
    constructor({ el, lat, lng }) {
        super({ el });

        this.lat = lat;
        this.lng = lng;
        this.marker = null;
    }

    createIcon() {
        return L.divIcon({
            className: "",
            html: `
                <div class="marker">
                    <div class="marker-pin"></div>
                </div>
            `,
            iconSize: [30, 42],
            iconAnchor: [15, 42],
        });
    }

    init() {
        if (!this.lat || !this.lng) return;

        this.initMap([this.lat, this.lng], 13);

        // 🔥 same rendering fix as PickerMap (but simpler)
        requestAnimationFrame(() => {
            this.map.invalidateSize(true);
        });

        this.marker = L.marker([this.lat, this.lng], {
            draggable: false,
        }).addTo(this.map);
    }
}
