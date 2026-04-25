<?php

use Livewire\Component;
use App\Models\Item;

new class extends Component {
    public $title = '';
    public $description = '';
    public $lat;
    public $lng;
    public $lost_at;
    public Item $item;

    public function mount(Item $item)
    {
        $this->item = $item;

        $translation = $item->translation();

        $this->title = $translation->title;
        $this->description = $translation->description;
        $this->lat = $item->location->lat;
        $this->lng = $item->location->lng;
        $this->lost_at = $item->lost_at?->format('M d, Y');
    }
};
?>

<div class="max-w-xl mx-auto py-6 space-y-4 text-foreground">

    <flux:heading size="lg">
        {{ __('Show Lost Item') }}
    </flux:heading>

    <div class="space-y-4">

        <h1 class="text-xl font-bold">{{ $title }}</h1>

        <p>{{ $description }}</p>

        <div id="show-map" data-lat="{{ $lat }}" data-lng="{{ $lng }}" class="w-full h-80 rounded-xl">
        </div>

        <p><strong>{{ __('Latitude') }}:</strong> {{ $lat }}</p>
        <p><strong>{{ __('Longitude') }}:</strong> {{ $lng }}</p>

        @if (!empty($item->library))
            <div class="space-y-2">
                <h2 class="font-semibold">{{ __('Photos') }}</h2>

                <div class="flex flex-col sm:flex-row sm:flex-wrap gap-3">
                    @foreach ($item->library as $image)
                        <div class="w-full sm:w-40 aspect-square overflow-hidden rounded-xl">
                            <img src="{{ $image['url'] }}" alt="Item image"
                                class="w-full h-full object-cover transition-transform duration-200 hover:scale-105" />
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="space-y-1 text-sm">
            <p>
                <span class="font-medium ">{{ __('Lost') }}:</span>
                {{ $lost_at }}
            </p>
        </div>

    </div>

</div>
