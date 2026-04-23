<?php

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Item;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use App\Filters\ItemFilters;

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

    public $bounds = null;

    protected $listeners = ['mapBoundsUpdated'];

    public function mapBoundsUpdated($bounds = null)
    {
        if (!$bounds) {
            return;
        }

        if ($this->bounds === $bounds) {
            return;
        }

        $this->bounds = $bounds;

        $this->resetPage();
    }

    public function getItemsProperty()
    {
        $query = Item::query()->with(['translations', 'location']);

        ItemFilters::apply($query, [
            'search' => $this->search,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'myItems' => $this->myItems,
            'locale' => app()->getLocale(),
        ]);

        if ($this->bounds) {
            $query->whereHas('location', function ($q) {
                $q->whereBetween('lat', [$this->bounds['south'], $this->bounds['north']])->whereBetween('lng', [$this->bounds['west'], $this->bounds['east']]);
            });
        }

        $queryForDebug = clone $query;

        logger('MAIN LIST IDS', [
            'ids' => $queryForDebug->pluck('id')->sort()->values()->all(),
            'count' => $queryForDebug->count(),
        ]);

        return $query->latest('lost_at')->paginate(10, pageName: 'item-filters');
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
            'locale' => app()->getLocale(),
        ]);
    }

    public function resetFilters()
    {
        $this->search = '';

        $this->dateFrom = null;

        $this->dateTo = null;

        $this->myItems = false;

        $this->resetPage();

        $this->dispatch('filtersUpdated', [
            'search' => '',
            'dateFrom' => null,
            'dateTo' => null,
            'myItems' => false,
            'locale' => app()->getLocale(),
        ]);
    }
};
?>


<div class="space-y-4">

    {{-- 🔎 Search --}}
    <flux:input wire:model.live.debounce.300ms="search" name="search" :label="__('Search')" type="text" autofocus
        :placeholder="__('Search lost items...')" clearable />

    {{-- ⚙️ Filters toggle --}}
    <details class="border border-border rounded">
        <summary class="cursor-pointer px-3 py-2 font-medium text-foreground">
            {{ __('Filters') }}
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

                    <flux:label class="text-foreground">{{ __('Only my items') }}</flux:label>

                    <flux:error name="myItems" class="text-foreground" />
                </flux:field>
            @endauth

            {{-- 🔄 Reset --}}
            <button wire:click="resetFilters" class="text-sm text-foreground underline">
                {{ __('Reset filters') }}
            </button>

        </div>
    </details>

    @if ($this->items->isEmpty())
        <div class="text-center text-surface-foreground/50 py-6">
            {{ __('No items found') }}
        </div>
    @endif

    {{-- 📦 Items list --}}
    <div class="gap-3 flex flex-col sm:flex-row flex-wrap" id="filtered-items">
        @foreach ($this->items as $item)
            <div wire:key="item-{{ $item->id }}"
                class="border border-border bg-surface text-surface-foreground rounded p-3 flex">
                <div>
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
                <div class="sm:mt-5 ps-2 flex flex-col gap-1 items-end w-full">
                    <flux:button size="sm">
                        {{ __('Details') }}
                    </flux:button>
                    <flux:button size="sm">
                        {{ __('Edit') }}
                    </flux:button>
                    <flux:button variant="danger" size="sm">
                        {{ __('Delete') }}
                    </flux:button>
                </div>
            </div>
        @endforeach
    </div>

    {{-- 📄 Pagination --}}
    <div>
        {{ $this->items->links(data: ['scrollTo' => '#filtered-items']) }}
    </div>

</div>
