<?php

use Livewire\Component;

new class extends Component {
    public $title = '';
    public $description = '';
    public $lat;
    public $lng;
    public $lost_at;
    public $createdItemId;

    protected $listeners = [
        'libraryValidated' => 'saveItemWithImages',
        'library-saved' => 'redirectAfterSave',
    ];

    public function save()
    {
        $this->validate([
            'title' => 'required|min:3',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'lost_at' => 'required|date',
        ]);

        // 🔥 ask child to validate images
        $this->dispatch('validateLibrary');
    }

    public function saveItemWithImages()
    {
        // 1️⃣ find or create location
        $location = \App\Models\Location::firstOrCreate([
            'lat' => round($this->lat, 6),
            'lng' => round($this->lng, 6),
        ]);

        // 2️⃣ create item with location_id
        $item = \App\Models\Item::create([
            'user_id' => auth()->id(),
            'title' => $this->title,
            'description' => $this->description,
            'lost_at' => $this->lost_at,
            'location_id' => $location->id,
        ]);

        $this->createdItemId = $item->id;

        // 3️⃣ trigger image saving
        $this->dispatch('updateLibraryModel', modelId: $item->id);
    }

    public function redirectAfterSave()
    {
        $item = \App\Models\Item::find($this->createdItemId);

        return redirect()->route('home', [
            'newItem' => json_encode([
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'lat' => $item->location->lat,
                'lng' => $item->location->lng,
            ]),
        ]);
    }
};
?>

<div class="max-w-xl mx-auto py-6 space-y-4">

    <flux:heading size="lg">
        {{ __('Create Lost Item') }}
    </flux:heading>

    <form wire:submit.prevent="save" class="space-y-4">

        <flux:input wire:model="title" :label="__('Title')" />

        <flux:textarea wire:model="description" :label="__('Description')" />

        <div wire:ignore id="picker-map" data-component-id="{{ $this->getId() }}" class="w-full h-80 rounded-xl"></div>

        <div class="mb-3">
            <flux:button type="button" onclick="document.getElementById('picker-map').useMyLocation()">
                {{ __('Use my location') }}
            </flux:button>
        </div>

        {{-- 📸 Images --}}
        <livewire:sortable-image-library />

        <flux:input wire:model.lazy="lat" type="number" step="any" :label="__('Latitude')" />

        <flux:input wire:model.lazy="lng" type="number" step="any" :label="__('Longitude')" />

        <flux:input wire:model="lost_at" type="date" :label="__('Lost at')" />

        <flux:button type="submit">
            {{ __('Save') }}
        </flux:button>

    </form>

</div>
