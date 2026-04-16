// resources/js/modules/MapService.js

export default class MapService {
    constructor() {
        this.cache = new Map();
    }

    async fetchPoints({ bbox, filters }) {
        console.log("fetchPoints called", { bbox, filters });

        const params = new URLSearchParams({
            bbox,
            search: filters.search ?? "",
            dateFrom: filters.dateFrom ?? "",
            dateTo: filters.dateTo ?? "",
            myItems: filters.myItems ? 1 : 0,
        });

        const key = params.toString();

        if (this.cache.has(key)) {
            return this.cache.get(key);
        }

        const res = await fetch(`/api/map-points?${key}`);
        const data = await res.json();

        this.cache.set(key, data);

        return data;
    }
}
