import BaseMap from "./BaseMap";
import MapService from "./MapService";
import MarkerLayer from "./layers/MarkerLayer";

export default class Map extends BaseMap {
    constructor({ el }) {
        super({ el });

        this.service = new MapService();
        this.layers = [];
    }

    init() {
        const { center, zoom } = this.getInitialView();

        this.initMap(center, zoom);

        this.registerLayers();
        this.bindEvents();

        this.layers.forEach((layer) => layer.onAdd());

        setTimeout(() => {
            this.handleInitialHighlight();
        }, 100);
    }

    getInitialView() {
        return {
            center: [50.2649, 19.0238],
            zoom: 13,
        };

        /* return {
            center: [52.0, 19.0],
            zoom: 6, // 🔥 zoomed out
        }; */
    }

    registerLayers() {
        this.layers.push(new MarkerLayer(this.map, this.service));
    }

    bindEvents() {
        let timeout = null;

        this.map.on("moveend", () => {
            clearTimeout(timeout);

            timeout = setTimeout(() => {
                const bounds = this.map.getBounds();

                this.layers.forEach((layer) => {
                    // 🔒 respect layer protections
                    if (layer.ignoreNextMove) {
                        layer.ignoreNextMove = false;
                        return;
                    }

                    if (layer.isProgrammaticMove) return;
                    if (layer.isAutoFitting) return;

                    // ✅ markers update
                    layer.onMove(bounds);

                    // ✅ Livewire update (LIST)
                    Livewire.dispatch("mapBoundsUpdated", {
                        bounds: {
                            west: bounds.getWest(),
                            south: bounds.getSouth(),
                            east: bounds.getEast(),
                            north: bounds.getNorth(),
                        },
                    });
                });
            }, 200);
        });

        window.addEventListener("highlightMapMarker", (e) => {
            const { locationId } = e.detail;

            this.layers.forEach((layer) => {
                if (layer.highlight) {
                    layer.highlight(locationId);
                }
            });
        });

        Livewire.on("filtersUpdated", (payload) => {
            const filters = payload[0]; // 🔥 FIX

            console.log("JS received filtersUpdated", filters);

            this.layers.forEach((layer) => {
                if (layer.setFilters) {
                    layer.setFilters(filters);
                }
            });
        });
    }

    handleInitialHighlight() {
        const locationId = this.el.dataset.highlightLocationId;
        if (!locationId) return;

        this.layers.forEach((layer) => {
            if (layer.highlight) {
                layer.highlight(parseInt(locationId));
            }
        });

        delete this.el.dataset.highlightLocationId;
    }
}
