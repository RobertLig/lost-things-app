import { initMainMap, initPickerMap, useMyLocation } from "./map";

document.addEventListener("livewire:navigated", () => {
    initMainMap();
    initPickerMap();
});

document.addEventListener("livewire:load", () => {
    initMainMap();
    initPickerMap();
});

window.useMyLocation = useMyLocation;
