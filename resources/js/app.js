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
