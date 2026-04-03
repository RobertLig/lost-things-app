// resources/js/app.js

import Map from "./modules/Map";

document.addEventListener("livewire:navigated", () => {
    const el = document.getElementById("map");
    if (!el) return;

    const newItem = el.dataset.newItem ? JSON.parse(el.dataset.newItem) : null;

    const map = new Map({
        el,
        newItem,
    });

    map.init();
});

// resources/js/app.js

import PickerMap from "./modules/PickerMap";

document.addEventListener("livewire:navigated", () => {
    const el = document.getElementById("picker-map");
    if (!el) return;

    const componentEl = el.closest("[wire\\:id]");
    if (!componentEl) return;

    const component = Livewire.find(el.dataset.componentId);

    const picker = new PickerMap({
        el,
        component,
    });

    picker.init();
});

import { /* initPickerMap,*/ useMyLocation } from "./map";

/* document.addEventListener("livewire:navigated", () => {
    //initPickerMap();
});

document.addEventListener("livewire:load", () => {
    //initPickerMap();
}); */

window.useMyLocation = useMyLocation;
