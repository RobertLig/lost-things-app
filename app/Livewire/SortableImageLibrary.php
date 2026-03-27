<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use App\Models\Item;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

class SortableImageLibrary extends Component
{
    use WithFileUploads;

    // Stored as a collection (array of ['url' => ...])
    #[Validate('array|max:4')]
    public $library; // Existing images (from DB)

    // For new uploads
    #[Validate(['files.*' => 'nullable|image|max:1024'])]
    public array $files = []; // Newly uploaded images

    public $allImages = []; // Combined and sorted images

    public $model;

    protected $listeners = ['validateLibrary' => 'onValidateLibrary',
                            'updateLibraryModel' => 'setModel',
                            ]; //'saveLibrary' => 'save'

    public function mount($model = null)
    {
        $this->model = $model;
        if ($this->model && $this->model->library) {
            $this->library = $this->model->library;
        } else {
            $this->library = collect();
        }
        $this->mergeImages();
    }

    public function updatedFiles()
    {
        $this->validate();
        $max = 4;
        $existing = $this->library->count();
        $new = count($this->files);

        if ($existing + $new > $max) {
            // Only allow up to (max - existing) new files
            $allowed = $max - $existing;
            $this->files = array_slice($this->files, 0, $allowed);
        }
        $this->mergeImages();
    }

    public function removeImage($index)
    {
        $image = $this->allImages[$index] ?? null;

        if (!$image) return;

        // Remove from files (new uploads)
        if (isset($image['is_new']) && $image['is_new']) {
            foreach ($this->files as $i => $file) {
                if ($file->getFilename() == $image['filename']) {
                    unset($this->files[$i]);
                    $this->files = array_values($this->files);
                    break;
                }
            }
        } else {
            // Remove from library (existing)
            foreach ($this->library as $i => $img) {
                if ($img['path'] == $image['path']) {
                    Storage::disk('public')->delete($img['path']);
                    $this->library = $this->library->forget($i)->values();
                    break;
                }
            }
        }

        $this->mergeImages();
    }

    public function moveImage($params = null)
    {
        if (!is_array($params)) return;
        $from = $params['oldIndex'];
        $to = $params['newIndex'];

        $images = $this->allImages;
        $moved = array_splice($images, $from, 1);
        array_splice($images, $to, 0, $moved);
        $this->allImages = array_values($images);

        // Sync new order to library/files
        $this->syncOrder();
    }

    public function onValidateLibrary()
    {
        try {
            $this->validate(); // Validate child (images)
            $this->dispatch('libraryValidated');
        } catch (\Illuminate\Validation\ValidationException $e) {
            //$this->dispatch('libraryValidationFailed');
            // Optionally rethrow for Livewire to show errors on child
            throw $e;
        }
    }

    public function setModel($modelId)
    {
        $this->model = Item::find($modelId);

        $this->save();
    }

    public function save()
    {
        $finalImages = [];
        foreach ($this->allImages as $img) {
            if (isset($img['is_new']) && $img['is_new']) {
                // Store new file
                foreach ($this->files as $i => $file) {
                    if ($file->getFilename() == $img['filename']) {
                        $path = $file->store('item-photos', 'public');
                        $finalImages[] = [
                            'url' => Storage::disk('public')->url($path),
                            'path' => $path,
                        ];
                        unset($this->files[$i]);
                        break;
                    }
                }
            } else {
                // Already stored
                $finalImages[] = [
                    'url' => $img['url'],
                    'path' => $img['path'],
                ];
            }
        }

        // Save to DB if model available
        if ($this->model) {
            $this->model->library = empty($finalImages) ? null : $finalImages;
            $this->model->save();
        }

        $this->library = collect($finalImages);
        $this->files = [];
        $this->mergeImages(); //(?)
        $this->dispatch('library-saved');
    }

    private function mergeImages()
    {
        $images = [];

        // Existing images
        foreach ($this->library as $img) {
            $images[] = [
                'url' => $img['url'],
                'path' => $img['path'],
                'is_new' => false,
            ];
        }

        // New images
        foreach ($this->files as $file) {
            $images[] = [
                'url' => $file->temporaryUrl(),
                'filename' => $file->getFilename(),
                'is_new' => true,
            ];
        }

        $this->allImages = $images;
    }

    // Ensure allImages order is synced to library/files
    private function syncOrder()
    {
        $newLibrary = collect();
        $newFiles = [];

        foreach ($this->allImages as $img) {
            if (isset($img['is_new']) && $img['is_new']) {
                // Find the file by filename
                foreach ($this->files as $file) {
                    if ($file->getFilename() == $img['filename']) {
                        $newFiles[] = $file;
                        break;
                    }
                }
            } else {
                $newLibrary->push(['url' => $img['url'], 'path' => $img['path']]);
            }
        }

        $this->library = $newLibrary;
        $this->files = $newFiles;
    }

    public function render()
    {
        return view('livewire.sortable-image-library', [
            'allImages' => $this->allImages,
        ]);
    }
}