<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public $photo;
    public $deletePhoto = false;

    public $user;

    protected function rules()
    {
        return [
            'photo' => 'nullable|image|max:2048', // 2MB
        ];
    }

    public function mount()
    {
        $this->user = Auth::user();
    }

    /**
     * Runs automatically when photo selected
     */
    public function updatedPhoto()
    {
        $this->validateOnly('photo');

        $this->deletePhoto = false;
    }

    /**
     * Explicit delete action
     */
    public function deletePhoto()
    {
        if ($this->user->photo_path) {
            Storage::disk('public')->delete($this->user->photo_path);
        }

        $this->user->photo_path = null;
        $this->user->save();

        $this->photo = null;
        $this->deletePhoto = true;
    }

    /**
     * Save button action
     */
    public function savePhoto()
    {
        // IMPORTANT: validate here too (your requirement)
        $this->validate();

        // Upload new photo
        if ($this->photo) {
            if ($this->user->photo_path) {
                Storage::disk('public')->delete($this->user->photo_path);
            }

            $path = $this->photo->store('profile-photos', 'public');

            $this->user->photo_path = $path;
            $this->user->save();

            $this->photo = null;
            $this->deletePhoto = false;
        }

        // Explicit deletion
        elseif ($this->deletePhoto) {
            if ($this->user->photo_path) {
                Storage::disk('public')->delete($this->user->photo_path);
            }

            $this->user->photo_path = null;
            $this->user->save();

            $this->deletePhoto = false;
        }

        session()->flash('status', 'Photo updated successfully.');
    }
};
?>

<div class="space-y-4">

    {{-- Preview --}}
    <div class="flex items-center gap-4">

        {{-- New preview --}}
        @if ($photo)
            <img src="{{ $photo->temporaryUrl() }}" class="w-20 h-20 rounded-full object-cover ring-2 ring-mint-400">

            {{-- Existing photo --}}
        @elseif ($user->photo_path)
            <img src="{{ Storage::url($user->photo_path) }}"
                class="w-20 h-20 rounded-full object-cover ring-2 ring-mint-400">
        @else
            <div class="w-20 h-20 rounded-full bg-mint-100 flex items-center justify-center text-mint-600">
                No photo
            </div>
        @endif

    </div>


    {{-- Upload input --}}
    <input type="file" wire:model="photo"
        class="block w-full text-sm
               file:mr-4 file:py-2 file:px-4
               file:rounded file:border-0
               file:bg-mint-500 file:text-white
               hover:file:bg-mint-600">

    @error('photo')
        <div class="text-red-500 text-sm">{{ $message }}</div>
    @enderror


    {{-- Buttons --}}
    <div class="flex gap-2">

        <button wire:click="savePhoto" class="px-4 py-2 bg-mint-500 text-white rounded hover:bg-mint-600">
            Save
        </button>

        @if ($user->photo_path || $photo)
            <button wire:click="deletePhoto" type="button"
                class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                Remove
            </button>
        @endif

    </div>


    {{-- Status --}}
    @if (session('status'))
        <div class="text-mint-600 text-sm">
            {{ session('status') }}
        </div>
    @endif

</div>
