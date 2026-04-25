export function createMarkerIcon({ count = null } = {}) {
    return L.divIcon({
        className: "",
        html: `
            <div class="marker">
                <div class="marker-pin"></div>
                ${
                    count !== null
                        ? `<div class="marker-badge">${count}</div>`
                        : ""
                }
            </div>
        `,
        iconSize: [30, 42],
        iconAnchor: [15, 42],
    });
}
