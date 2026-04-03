<?php

use Livewire\Component;
use App\Models\Item;

new class extends Component {
    public $items = [];

    protected $listeners = ['locationSelected'];

    public function locationSelected($locationId)
    {
        $this->items = Item::where('location_id', $locationId)->get()->toArray();
    }
};
?>

<div class="p-4 text-foreground">
    @if (count($items))
        <h3 class="font-bold mb-2">{{ __('Items in this location') }}</h3>

        @foreach ($items as $item)
            <div class="border-b border-border py-2 cursor-pointer hover:bg-muted/50 transition"
                wire:click="$dispatch('highlightMapMarker', { locationId: {{ $item['location_id'] }} })" <strong>
                {{ $item['title'] }}</strong><br>
                <small>{{ $item['description'] }}</small>
            </div>
        @endforeach
    @else
        <p class="text-foreground/50">{{ __('Click a marker to see lost items') }}</p>
    @endif
</div>
