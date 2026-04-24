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
        $this->lost_at = $item->lost_at?->format('Y-m-d');
    }
};
?>

<div class="max-w-xl mx-auto py-6 space-y-4">

    <flux:heading size="lg">
        {{ __('Show Lost Item') }}
    </flux:heading>

    <div class="space-y-4">

        <h1 class="text-xl font-bold">{{ $title }}</h1>

        <p>{{ $description }}</p>

        <div id="show-map" data-lat="{{ $lat }}" data-lng="{{ $lng }}" class="w-full h-80 rounded-xl">
        </div>

        {{-- 📸 Images --}}
        <livewire:sortable-image-library :model="$item" />

        <p><strong>{{ __('Latitude') }}:</strong> {{ $lat }}</p>
        <p><strong>{{ __('Longitude') }}:</strong> {{ $lng }}</p>

        <p>{{ $lost_at }}</p>

    </div>

</div>
