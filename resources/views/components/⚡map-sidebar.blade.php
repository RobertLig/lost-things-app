<?php

use Livewire\Component;
use App\Models\Item;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $items = [];

    protected $listeners = ['locationSelected'];

    public function locationSelected($locationId)
    {
        $this->items = Item::where('location_id', $locationId)->get()->toArray();
    }
};
?>

<div class="p-4 text-foreground relative">
    {{-- 🔄 SKELETON LOADER --}}
    <div wire:loading.delay.short wire:target="locationSelected"
        class="absolute inset-0 bg-background/80 backdrop-blur-sm z-10 p-4">

        <h3 class="font-bold mb-2">{{ __('Items in this location') }}</h3>

        @for ($i = 0; $i < 3; $i++)
            <div class="border-b border-border py-2">
                <flux:skeleton.group animate="shimmer">
                    <flux:skeleton.line class="mb-2 w-2/3" />

                    <flux:skeleton.line class="w-3/4" />
                </flux:skeleton.group>
            </div>
        @endfor
    </div>

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
