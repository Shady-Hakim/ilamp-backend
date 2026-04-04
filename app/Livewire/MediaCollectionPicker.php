<?php

namespace App\Livewire;

use App\Models\MediaAsset;
use App\Services\MediaLibraryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class MediaCollectionPicker extends Component
{
    use WithFileUploads;

    public ?Model $record = null;

    public string $collection = '';

    public string $label = '';

    public bool $multiple = false;

    public bool $modalOpen = false;

    public string $activeTab = 'library';

    public string $search = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $uploadFile = null;

    public string $uploadError = '';

    #[Computed]
    public function currentMedia(): Collection
    {
        return $this->record?->getMedia($this->collection) ?? collect();
    }

    #[Computed]
    public function libraryAssets(): Collection
    {
        return MediaAsset::query()
            ->when(
                filled($this->search),
                fn ($q) => $q->where(function ($q): void {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('filename', 'like', "%{$this->search}%");
                })
            )
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();
    }

    public function openModal(): void
    {
        $this->modalOpen = true;
        $this->search = '';
        $this->uploadFile = null;
        $this->uploadError = '';
        $this->activeTab = 'library';
        unset($this->libraryAssets);
    }

    public function selectAsset(int $assetId): void
    {
        if (! $this->record) {
            return;
        }

        $asset = MediaAsset::find($assetId);
        if (! $asset) {
            return;
        }

        $path = $asset->storage_disk === MediaAsset::DISK_PUBLIC
            ? storage_path('app/public/'.ltrim($asset->relative_path, '/'))
            : public_path(ltrim($asset->relative_path, '/'));

        if (! is_file($path)) {
            return;
        }

        if (! $this->multiple) {
            $this->record->clearMediaCollection($this->collection);
        }

        $this->record->addMedia($path)
            ->preservingOriginal()
            ->usingFileName($asset->filename)
            ->toMediaCollection($this->collection);

        unset($this->currentMedia);

        if (! $this->multiple) {
            $this->modalOpen = false;
        }
    }

    public function removeMedia(int $mediaId): void
    {
        if (! $this->record) {
            return;
        }

        $media = $this->record
            ->getMedia($this->collection)
            ->first(fn ($m) => $m->id === $mediaId);

        if ($media) {
            $media->delete();
            $this->record->refresh();
        }

        unset($this->currentMedia);
    }

    public function clearAll(): void
    {
        if ($this->record) {
            $this->record->clearMediaCollection($this->collection);
        }
        unset($this->currentMedia);
    }

    public function doUpload(): void
    {
        $this->uploadError = '';

        if (! $this->record) {
            $this->uploadError = 'Save the record first before uploading.';

            return;
        }

        try {
            $this->validate(['uploadFile' => 'required|file|image|max:10240']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->uploadError = $e->validator->errors()->first();

            return;
        }

        if (! $this->multiple) {
            $this->record->clearMediaCollection($this->collection);
        }

        $originalName = $this->uploadFile->getClientOriginalName();

        $media = $this->record
            ->addMedia($this->uploadFile->getRealPath())
            ->usingFileName($originalName)
            ->toMediaCollection($this->collection);

        // Sync to the MediaAsset library (non-fatal if it fails)
        try {
            app(MediaLibraryService::class)->syncUploadedFile(
                MediaAsset::DISK_PUBLIC,
                $media->getPathRelativeToRoot(),
            );
        } catch (\Throwable) {
        }

        $this->uploadFile = null;
        unset($this->currentMedia, $this->libraryAssets);

        if (! $this->multiple) {
            $this->modalOpen = false;
        }
    }

    public function render(): View
    {
        return view('livewire.media-collection-picker');
    }
}
