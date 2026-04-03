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

import { /*initMainMap,*/ initPickerMap, useMyLocation } from "./map";

document.addEventListener("livewire:navigated", () => {
    //initMainMap();
    initPickerMap();
});

document.addEventListener("livewire:load", () => {
    //initMainMap();
    initPickerMap();
});

window.useMyLocation = useMyLocation;
