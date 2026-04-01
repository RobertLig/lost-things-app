<?php

use Livewire\Component;

new class extends Component {
    public $items = [];

    protected $listeners = ['locationSelected'];

    public function locationSelected($data)
    {
        $this->items = $data['items'];
    }
};
?>

<div class="p-4">
    @if (count($items))
        <h3 class="font-bold mb-2">Items in this location</h3>

        @foreach ($items as $item)
            <div class="border-b py-2">
                <strong>{{ $item['title'] }}</strong><br>
                <small>{{ $item['description'] }}</small>
            </div>
        @endforeach
    @else
        <p class="text-gray-500">Click a marker to see items</p>
    @endif
</div>
