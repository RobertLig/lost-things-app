// resources/js/modules/MapService.js

export default class MapService {
    constructor() {
        this.cache = new Map();
    }

    async fetchPoints(bbox) {
        if (this.cache.has(bbox)) {
            return this.cache.get(bbox);
        }

        const res = await fetch(`/api/map-points?bbox=${bbox}`);
        const data = await res.json();

        this.cache.set(bbox, data);

        return data;
    }
}
