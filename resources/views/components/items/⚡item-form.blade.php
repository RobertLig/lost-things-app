<?php

use Livewire\Component;
use App\Services\TranslationService;

new class extends Component {
    public $title = '';
    public $description = '';
    public $lat;
    public $lng;
    public $lost_at;
    public $createdItemId;
    public ?\App\Models\Item $item = null;

    protected $listeners = [
        'libraryValidated' => 'saveItemWithImages',
        'library-saved' => 'redirectAfterSave',
    ];

    public function mount(\App\Models\Item $item = null)
    {
        //dd($item->lost_at);
        if ($item) {
            $this->item = $item;

            $translation = $item->translation();

            $this->title = $translation->title;
            $this->description = $translation->description;
            $this->lat = $item->location->lat;
            $this->lng = $item->location->lng;
            $this->lost_at = $item->lost_at?->format('Y-m-d');
        }
    }

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

    public function saveItemWithImages(TranslationService $translator)
    {
        if ($this->item) {
            $this->updateItem($translator);
        } else {
            $this->createItem($translator);
        }
    }

    protected function updateItem(TranslationService $translator)
    {
        $location = \App\Models\Location::firstOrCreate([
            'lat' => round($this->lat, 6),
            'lng' => round($this->lng, 6),
        ]);

        $this->item->update([
            'lost_at' => \Carbon\Carbon::parse($this->lost_at),
            'location_id' => $location->id,
        ]);

        // update translations instead of creating new ones
        foreach ($this->item->translations as $translation) {
            if ($translation->locale === app()->getLocale()) {
                $translation->update([
                    'title' => $this->title,
                    'description' => $this->description,
                ]);
            } else {
                $translation->update([
                    'title' => $translator->translate($this->title, $translation->locale),
                    'description' => $translator->translate($this->description, $translation->locale),
                ]);
            }
        }

        $this->createdItemId = $this->item->id;

        $this->dispatch('updateLibraryModel', modelId: $this->item->id);
    }

    public function createItem(TranslationService $translator)
    {
        // 1️⃣ find or create location
        $location = \App\Models\Location::firstOrCreate([
            'lat' => round($this->lat, 6),
            'lng' => round($this->lng, 6),
        ]);

        // 2️⃣ create item with location_id
        $item = \App\Models\Item::create([
            'user_id' => auth()->id(),
            'lost_at' => \Carbon\Carbon::parse($this->lost_at),
            'library' => [],
            'location_id' => $location->id,
        ]);

        // 3️⃣ translate
        //$translator = app(TranslationService::class);

        $sourceLocale = app()->getLocale(); // 'pl' or 'en'
        $targetLocale = $sourceLocale === 'pl' ? 'en' : 'pl';

        if ($sourceLocale === 'pl') {
            $titleEn = $translator->translate($this->title, 'en');
            $descEn = $translator->translate($this->description, 'en');

            // 4️⃣ save translations
            $item->translations()->createMany([
                [
                    'locale' => 'pl',
                    'title' => $this->title,
                    'description' => $this->description,
                ],
                [
                    'locale' => 'en',
                    'title' => $titleEn,
                    'description' => $descEn,
                ],
            ]);
        } elseif ($sourceLocale === 'en') {
            $titlePl = $translator->translate($this->title, 'pl');
            $descPl = $translator->translate($this->description, 'pl');

            // 4️⃣ save translations
            $item->translations()->createMany([
                [
                    'locale' => 'pl',
                    'title' => $titlePl,
                    'description' => $descPl,
                ],
                [
                    'locale' => 'en',
                    'title' => $this->title,
                    'description' => $this->description,
                ],
            ]);
        }

        $this->createdItemId = $item->id;

        // 3️⃣ trigger image saving
        $this->dispatch('updateLibraryModel', modelId: $item->id);
    }

    public function redirectAfterSave()
    {
        $item = \App\Models\Item::find($this->createdItemId);

        return redirect()->route('home')->with('new_item_id', $item->id);
    }
};
?>

<div class="max-w-xl mx-auto py-6 space-y-4">

    <flux:heading size="lg">
        {{ $item ? __('Edit Lost Item') : __('Create Lost Item') }}
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
        <livewire:sortable-image-library :model="$item" />

        <flux:input wire:model.lazy="lat" type="number" step="any" :label="__('Latitude')" />

        <flux:input wire:model.lazy="lng" type="number" step="any" :label="__('Longitude')" />

        <flux:input wire:model.lazy="lost_at" type="date" :label="__('Lost at')" />

        <flux:button type="submit">
            {{ __('Save') }}
        </flux:button>

    </form>

</div>
