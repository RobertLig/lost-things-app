<?php

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Item;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;

new class extends Component {
    use WithPagination, WithoutUrlPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?string $dateFrom = null;

    #[Url]
    public ?string $dateTo = null;

    #[Url]
    public bool $myItems = false;

    public function getItemsProperty()
    {
        return Item::query()
            ->with(['translations', 'location'])
            ->when($this->search, function ($query) {
                $query->whereHas('translations', function ($q) {
                    $q->where('locale', app()->getLocale())->where(function ($q2) {
                        $q2->where('title', 'like', "%{$this->search}%")->orWhere('description', 'like', "%{$this->search}%");
                    });
                });
            })
            ->when($this->dateFrom, fn($q) => $q->whereDate('lost_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('lost_at', '<=', $this->dateTo))
            ->when($this->myItems && auth()->check(), fn($q) => $q->where('user_id', auth()->id()))
            ->latest('lost_at')
            ->paginate(10);
    }
};
?>


<div class="space-y-4">

    {{-- 🔎 Search --}}
    <flux:input wire:model.live.debounce.300ms="search" name="search" :label="__('Search')" :value="old('search')"
        type="text" autofocus placeholder="Search lost items..." clearable />

    {{-- ⚙️ Filters toggle --}}
    <details class="border border-border rounded">
        <summary class="cursor-pointer px-3 py-2 font-medium text-foreground">
            Filters
        </summary>

        <div class="p-3 space-y-3">

            {{-- 📅 Date range --}}
            <div class="grid grid-cols-2 gap-2">
                {{-- <input type="date" wire:model="dateFrom" class="border rounded px-2 py-1"> --}}
                {{-- <input type="date" wire:model="dateTo" class="border rounded px-2 py-1"> --}}

                <flux:input wire:model="dateFrom" name="dateFrom" :label="__('Date From')" :value="old('dateFrom')"
                    type="date" />

                <flux:input wire:model="dateTo" name="dateTo" :label="__('Date To')" :value="old('dateTo')"
                    type="date" />
            </div>

            {{-- 👤 My items --}}
            @auth
                {{-- <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="myItems">
                        <span>Only my items</span>
                    </label> --}}

                <flux:field variant="inline">
                    <flux:checkbox wire:model="myItems" />

                    <flux:label class="text-foreground">Only my items</flux:label>

                    <flux:error name="myItems" class="text-foreground" />
                </flux:field>
            @endauth

            {{-- 🔄 Reset --}}
            <button wire:click="$set('search',''); $set('dateFrom',null); $set('dateTo',null); $set('myItems',false)"
                class="text-sm text-foreground underline">
                Reset filters
            </button>

        </div>
    </details>

    {{-- 📦 Items list --}}
    <div class="space-y-3 space-x-3 sm:flex flex-wrap" id="filtered-items">
        @foreach ($this->items as $item)
            <div class="border border-border bg-surface text-surface-foreground rounded p-3">

                <div class="font-semibold">
                    {{ $item->translation()?->title }}
                </div>

                <div class="text-sm text-surface-foreground/50">
                    {{ $item->lost_at->format('Y-m-d') }}
                </div>

                @if ($item->library->isNotEmpty())
                    <img src="{{ $item->library[0]['url'] }}" class="mt-2 rounded max-h-40 object-cover">
                @endif

            </div>
        @endforeach
    </div>

    {{-- 📄 Pagination --}}
    <div>
        {{ $this->items->links(data: ['scrollTo' => '#filtered-items']) }}
    </div>

</div>
