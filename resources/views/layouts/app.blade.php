<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="bg-background!">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
