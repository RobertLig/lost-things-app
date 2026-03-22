<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div>
    <div id="map" style="height: 400px;"></div>

    <script>
        document.addEventListener('livewire:navigated', () => {
            if (window.mapInitialized) return;

            window.mapInitialized = true;

            const map = L.map('map').setView([50.2649, 19.0238], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
        });
    </script>
</div>
