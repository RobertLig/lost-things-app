<?php

use Livewire\Component;

new class extends Component {
    public $title = '';
    public $description = '';
    public $lat;
    public $lng;
    public $lost_at;

    public function save()
    {
        $this->validate([
            'title' => 'required|min:3',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'lost_at' => 'required|date',
        ]);

        $item = \App\Models\Item::create([
            'user_id' => auth()->id(),
            'title' => $this->title,
            'description' => $this->description,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'lost_at' => $this->lost_at,
        ]);

        return redirect()
            ->route('home')
            ->with('new_item', [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'lat' => $item->lat,
                'lng' => $item->lng,
            ]);
    }
};
?>

<div class="max-w-xl mx-auto py-6 space-y-4">

    <flux:heading size="lg">
        {{ __('Create Lost Item') }}
    </flux:heading>

    <div id="picker-map" class="w-full h-80 rounded-xl"></div>

    <form wire:submit.prevent="save" class="space-y-4">

        <flux:input wire:model="title" :label="__('Title')" />

        <flux:textarea wire:model="description" :label="__('Description')" />

        <flux:input wire:model="lat" type="number" step="any" :label="__('Latitude')" />

        <flux:input wire:model="lng" type="number" step="any" :label="__('Longitude')" />

        <flux:input wire:model="lost_at" type="datetime-local" :label="__('Lost at')" />

        <flux:button type="submit">
            {{ __('Save') }}
        </flux:button>

    </form>

</div>
