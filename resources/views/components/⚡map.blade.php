<?php

use Livewire\Component;
use App\Models\Item;

new class extends Component {
    //
};
?>

<div id="map" style="height: 400px;"
    data-highlight-location-id="{{ session('new_item_id') ? \App\Models\Item::find(session('new_item_id'))?->location_id : '' }}">
</div>
