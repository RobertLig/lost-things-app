<div>
    <ul id="image-list" x-data x-init="Sortable.create($el, {
        animation: 150,
        onEnd: function(evt) {
            $wire.moveImage({ oldIndex: evt.oldIndex, newIndex: evt.newIndex });
        }
    })">
        @foreach ($allImages as $i => $img)
            <li class="flex items-center gap-2 bg-base-100 rounded-lg p-2" data-id="{{ $i }}">
                <img src="{{ $img['url'] }}" class="w-24 h-24 object-cover rounded-lg" />

                <flux:button type="submit" type="button" wire:click="removeImage({{ $i }})" class="ml-2">
                    {{ __('Delete') }}
                </flux:button>
                {{-- <button type="button" wire:click="removeImage({{ $i }})"
                    class="btn btn-error btn-sm ml-2">Delete</button> --}}
            </li>
        @endforeach
    </ul>

    @if (count($allImages) < 4)
        <div>
            <label
                class="inline-block py-2 px-4 mt-3 bg-white hover:bg-zinc-50 dark:bg-zinc-700 dark:hover:bg-zinc-600/75 text-zinc-800 dark:text-white border border-zinc-200 hover:border-zinc-200 border-b-zinc-300/80 dark:border-zinc-600 dark:hover:border-zinc-600 shadow-xs  text-sm rounded-lg relative items-center font-medium justify-center gap-2 whitespace-nowrap  cursor-pointer">
                {{ __('Add Images') }}
                <input type="file" multiple wire:model="files" accept="image/*" class="hidden" />
            </label>
            <p class="mt-2 text-xs" style="color: var(--p);">
                {{ __('Tip: To add multiple images, select them all at once in the file picker.') }}
            </p>
        </div>
    @endif
    @error('files.*')
        <span class="text-error">{{ $message }}</span>
    @enderror
</div>
