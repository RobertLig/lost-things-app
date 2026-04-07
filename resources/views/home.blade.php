<x-layouts::app :title="__('Home')">
    <livewire:map />
    @auth
        <flux:button :href="route('items.create')" class="mt-3!"> {{-- wire:navigate --}}
            {{ __('Add Lost Item') }}
        </flux:button>
    @endauth

    <livewire:map-sidebar />
</x-layouts::app>
