import Map from "./modules/Map";

let mapInstance = null;

document.addEventListener("livewire:navigated", () => {
    const el = document.getElementById("map");
    if (!el) return;

    mapInstance = new Map({ el });
    mapInstance.init();
});

import PickerMap from "./modules/PickerMap";

let pickerInstance = null;

document.addEventListener("livewire:navigated", () => {
    const el = document.getElementById("picker-map");

    if (!el) return;

    // 🔥 destroy previous instance
    if (pickerInstance) {
        pickerInstance.destroy();
        pickerInstance = null;
    }

    // 🔥 CRITICAL: clear leftover global drag handlers
    document.onmousemove = null;
    document.onmouseup = null;

    const component = Livewire.find(el.dataset.componentId);

    pickerInstance = new PickerMap({
        el,
        component,
    });

    pickerInstance.init();

    el.useMyLocation = () => pickerInstance.useMyLocation();
});

function registerGlobalListeners() {
    window.addEventListener("scrollSidebarToTop", () => {
        const el = document.getElementById("sidebar-map");

        if (el) {
            el.scrollTo({
                top: 0,
                behavior: "smooth",
            });
        }
    });
}

registerGlobalListeners();

import ShowMap from "./modules/ShowMap";

let showMapInstance = null;

document.addEventListener("livewire:navigated", () => {
    const el = document.getElementById("show-map");
    if (!el) return;

    if (showMapInstance) {
        showMapInstance.destroy();
        showMapInstance = null;
    }

    const lat = parseFloat(el.dataset.lat);
    const lng = parseFloat(el.dataset.lng);

    showMapInstance = new ShowMap({
        el,
        lat,
        lng,
    });

    showMapInstance.init();
});

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import "./echo";
