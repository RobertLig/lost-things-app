<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Language Settings') }}</flux:heading>

    <x-settings.layout :heading="__('Language')" :subheading="__('Change the page language')">
        <flux:dropdown>
            <flux:button variant="filled">
                {{ strtoupper($current) }}
            </flux:button>

            <flux:menu class="bg-[var(--color-surface)]! border-[var(--color-border)]!">
                @foreach ($locales as $localeCode => $properties)
                    <flux:menu.item href="{{ route('locale.set', $localeCode) }}"
                        class="text-[var(--color-surface-foreground)]! hover:bg-[var(--color-accent)]!
                       hover:text-[var(--color-accent-foreground)]!">
                        {{ $properties['native'] }}
                    </flux:menu.item>
                @endforeach
            </flux:menu>
        </flux:dropdown>
    </x-settings.layout>
</section>
