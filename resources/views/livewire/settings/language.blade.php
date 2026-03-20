<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Language Settings') }}</flux:heading>

    <x-settings.layout :heading="__('Language')" :subheading="__('Change the page language')">
        <flux:dropdown>
            <flux:button>
                {{ strtoupper($current) }}
            </flux:button>

            <flux:menu>
                @foreach ($locales as $localeCode => $properties)
                    <flux:menu.item href="{{ route('locale.set', $localeCode) }}">
                        {{ $properties['native'] }}
                    </flux:menu.item>

                    {{-- <flux:menu.item href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
                        {{ $properties['native'] }}
                    </flux:menu.item> --}}
                @endforeach
            </flux:menu>
        </flux:dropdown>
    </x-settings.layout>
</section>
