<?php

use Livewire\Component;

new class extends Component {
    public $newItem = null;

    public function mount()
    {
        $this->newItem = request()->get('newItem') ? json_decode(request()->get('newItem'), true) : null;
    }
};
?>

<div>
    <div id="map" style="height: 400px;" data-new-item='@json($newItem)'></div>
</div>
