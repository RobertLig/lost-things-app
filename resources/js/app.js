// resources/js/app.js

import Map from "./modules/Map";

let mapInstance = null;
let hookRegistered = false;

document.addEventListener("livewire:navigated", () => {
    const el = document.getElementById("map");
    if (!el) return;

    mapInstance = new Map({ el });
    mapInstance.init();

    if (!hookRegistered) {
        Livewire.hook("message.processed", () => {
            const el = document.getElementById("map");
            if (!el) return;

            const locationId = el.dataset.highlightLocationId;

            if (locationId && mapInstance) {
                window.dispatchEvent(
                    new CustomEvent("highlightMapMarker", {
                        detail: { locationId: parseInt(locationId) },
                    }),
                );

                delete el.dataset.highlightLocationId;
            }
        });

        hookRegistered = true;
    }
});

// resources/js/app.js

import PickerMap from "./modules/PickerMap";

document.addEventListener("livewire:navigated", () => {
    const el = document.getElementById("picker-map");
    if (!el) return;

    const component = Livewire.find(el.dataset.componentId);

    const picker = new PickerMap({
        el,
        component,
    });

    picker.init();

    // 🔥 attach method directly to element
    el.useMyLocation = () => picker.useMyLocation();
});
