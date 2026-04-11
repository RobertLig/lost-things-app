<?php

use Livewire\Component;
use App\Models\Item;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;

new class extends Component {
    use WithPagination, WithoutUrlPagination;

    public $locationId = null;

    protected $listeners = ['locationSelected'];

    public function locationSelected($locationId)
    {
        $this->locationId = $locationId;

        // 🔥 reset to page 1 when new marker clicked
        $this->resetPage();
    }

    public function getItemsProperty()
    {
        if (!$this->locationId) {
            return collect();
        }

        return Item::with('translations')->where('location_id', $this->locationId)->latest()->paginate(5);
    }

    public function updatedPage()
    {
        $this->dispatch('scrollSidebarToTop');
    }
};
?>

<div id="sidebar-map" class="p-4 text-foreground relative overflow-y-auto">
    {{-- 🔄 SKELETON LOADER --}}
    <div wire:loading.delay.short wire:target="locationSelected,page"
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

    @if ($this->items->count())
        <h3 class="font-bold mb-2">{{ __('Items in this location') }}</h3>

        @foreach ($this->items as $item)
            <div class="border-b border-border py-2 cursor-pointer hover:bg-muted/50 transition"
                wire:click="$dispatch('highlightMapMarker', { locationId: {{ $item['location_id'] }} })" <strong>
                {{ $item['title'] }}</strong><br>
                <small>{{ $item['description'] }}</small>
            </div>
        @endforeach

        {{-- 🔥 PAGINATION LINKS --}}
        <div class="mt-3">
            {{ $this->items->links(data: ['scrollTo' => false]) }}
        </div>
    @else
        <p class="text-foreground/50">{{ __('Click a marker to see lost items') }}</p>
    @endif
</div>
