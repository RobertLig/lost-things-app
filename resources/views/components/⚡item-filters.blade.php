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

    public ?float $north = null;
    public ?float $south = null;
    public ?float $east = null;
    public ?float $west = null;

    protected $listeners = ['mapBoundsUpdated'];

    public function mapBoundsUpdated($bounds)
    {
        $this->north = $bounds['north'];
        $this->south = $bounds['south'];
        $this->east = $bounds['east'];
        $this->west = $bounds['west'];

        $this->resetPage();
    }

    public function getItemsProperty()
    {
        return Item::query()
            ->with([
                'translations' => function ($q) {
                    $q->where('locale', app()->getLocale());
                },
                'location',
            ])
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
            ->when($this->north, function ($query) {
                $query->whereHas('location', function ($q) {
                    $south = $this->south;
                    $north = $this->north;
                    $west = $this->west;
                    $east = $this->east;

                    // small padding (prevents edge clipping)
                    $pad = 0.01;

                    $q->whereBetween('lat', [$south - $pad, $north + $pad])->whereBetween('lng', [$west - $pad, $east + $pad]);
                });
            })
            ->latest('lost_at')
            ->paginate(10);
    }

    public function updated($property)
    {
        $this->resetPage();

        logger('filtersUpdated fired', [
            'search' => $this->search,
        ]);

        $this->dispatch('filtersUpdated', [
            'search' => $this->search,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'myItems' => $this->myItems,
        ]);
    }
};
?>


<div class="space-y-4">

    {{-- 🔎 Search --}}
    <flux:input wire:model.live.debounce.300ms="search" name="search" :label="__('Search')" type="text" autofocus
        placeholder="Search lost items..." clearable />

    {{-- ⚙️ Filters toggle --}}
    <details class="border border-border rounded">
        <summary class="cursor-pointer px-3 py-2 font-medium text-foreground">
            Filters
        </summary>

        <div class="p-3 space-y-3">

            {{-- 📅 Date range --}}
            <div class="grid grid-cols-2 gap-2">
                <flux:input wire:model.live="dateFrom" name="dateFrom" :label="__('Date From')" type="date" />

                <flux:input wire:model.live="dateTo" name="dateTo" :label="__('Date To')" type="date" />
            </div>

            {{-- 👤 My items --}}
            @auth
                <flux:field variant="inline">
                    <flux:checkbox wire:model.live="myItems" />

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

    @if ($this->items->isEmpty())
        <div class="text-center text-surface-foreground/50 py-6">
            No items found
        </div>
    @endif

    {{-- 📦 Items list --}}
    <div class="gap-3 sm:flex flex-wrap" id="filtered-items">
        @foreach ($this->items as $item)
            <div wire:key="item-{{ $item->id }}"
                class="border border-border bg-surface text-surface-foreground rounded p-3">

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
