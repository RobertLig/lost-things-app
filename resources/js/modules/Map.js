// resources/js/modules/Map.js

import BaseMap from "./BaseMap";
import MapService from "./MapService";
import MarkerLayer from "./MarkerLayer";

export default class Map extends BaseMap {
    constructor({ el }) {
        super({ el });

        this.service = new MapService();
        this.markerLayer = null;

        this.lastBbox = null;
    }

    init() {
        const { center, zoom } = this.getInitialView();

        this.initMap(center, zoom);

        this.markerLayer = new MarkerLayer(this.map);

        this.bindEvents();

        this.loadMarkers();

        setTimeout(() => {
            this.updateVisibleMarkers();
            this.handleInitialHighlight();
        }, 100);
    }

    getInitialView() {
        return {
            center: [50.2649, 19.0238],
            zoom: 13,
        };
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

            if (this.markerLayer.has(key)) return;

            this.markerLayer.add(point);
        });

        this.updateVisibleMarkers();
    }

    bindEvents() {
        let timeout = null;

        this.map.on("moveend", () => {
            clearTimeout(timeout);

            timeout = setTimeout(() => {
                this.loadMarkers();
            }, 200);
        });

        window.addEventListener("highlightMapMarker", (e) => {
            const { locationId } = e.detail;
            this.markerLayer.highlight(locationId);
        });
    }

    handleInitialHighlight() {
        const locationId = this.el.dataset.highlightLocationId;

        if (!locationId) return;

        this.markerLayer.highlight(parseInt(locationId));

        delete this.el.dataset.highlightLocationId;
    }

    updateVisibleMarkers() {
        const bounds = this.map.getBounds();
        this.markerLayer.updateVisibility(bounds);
    }
}
