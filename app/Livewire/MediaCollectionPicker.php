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

    public int $libraryPage = 1;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $uploadFile = null;

    public string $uploadError = '';

    /** MediaAsset IDs selected before the record is first saved. */
    public array $pendingMediaIds = [];

    #[Computed]
    public function currentMedia(): Collection
    {
        return $this->record?->getMedia($this->collection) ?? collect();
    }

    #[Computed]
    public function pendingPreviews(): Collection
    {
        if ($this->record || empty($this->pendingMediaIds)) {
            return collect();
        }

        return MediaAsset::whereIn('id', $this->pendingMediaIds)->get();
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
            ->limit($this->libraryPage * 24 + 1)
            ->get();
    }

    public function openModal(): void
    {
        $this->modalOpen = true;
        $this->search = '';
        $this->libraryPage = 1;
        $this->uploadFile = null;
        $this->uploadError = '';
        $this->activeTab = 'library';
        unset($this->libraryAssets);
    }

    public function updatedSearch(): void
    {
        $this->libraryPage = 1;
        unset($this->libraryAssets);
    }

    public function loadMoreLibrary(): void
    {
        if ($this->libraryAssets->count() <= $this->libraryPage * 24) {
            return;
        }
        $this->libraryPage++;
        unset($this->libraryAssets);
    }

    public function selectAsset(int $assetId): void
    {
        abort_unless(auth()->check(), 403);

        if (! $this->record) {
            // No record yet – queue for attachment after save
            if (! $this->multiple) {
                $this->pendingMediaIds = [$assetId];
            } elseif (! in_array($assetId, $this->pendingMediaIds)) {
                $this->pendingMediaIds[] = $assetId;
            }

            $this->dispatch('pending-media-updated', collection: $this->collection, ids: $this->pendingMediaIds);
            unset($this->pendingPreviews);

            if (! $this->multiple) {
                $this->modalOpen = false;
            }

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

    public function removePending(int $assetId): void
    {
        abort_unless(auth()->check(), 403);

        $this->pendingMediaIds = array_values(
            array_filter($this->pendingMediaIds, fn ($id) => $id !== $assetId)
        );
        $this->dispatch('pending-media-updated', collection: $this->collection, ids: $this->pendingMediaIds);
        unset($this->pendingPreviews);
    }

    public function clearPending(): void
    {
        abort_unless(auth()->check(), 403);

        $this->pendingMediaIds = [];
        $this->dispatch('pending-media-updated', collection: $this->collection, ids: []);
        unset($this->pendingPreviews);
    }

    public function removeMedia(int $mediaId): void
    {
        abort_unless(auth()->check(), 403);

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
        abort_unless(auth()->check(), 403);

        if ($this->record) {
            $this->record->clearMediaCollection($this->collection);
        }
        unset($this->currentMedia);
    }

    public function doUpload(): void
    {
        abort_unless(auth()->check(), 403);

        $this->uploadError = '';

        if (! $this->record) {
            // Upload to storage first, create a MediaAsset, queue ID for attachment after save
            try {
                // SVG is intentionally excluded: browsers execute inline scripts in SVG served as image/svg+xml.
                $this->validate(['uploadFile' => 'required|file|mimes:jpg,jpeg,png,gif,webp,avif,bmp,ico|max:10240']);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->uploadError = $e->validator->errors()->first();

                return;
            }

            $ext          = strtolower($this->uploadFile->getClientOriginalExtension());
            $base         = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($this->uploadFile->getClientOriginalName(), PATHINFO_FILENAME));
            $originalName = ($base ?: 'upload') . '.' . $ext;
            $path = $this->uploadFile->storeAs('uploads/'.date('Y/m'), $originalName, 'public');

            if (! $path) {
                $this->uploadError = 'Failed to store the uploaded file.';

                return;
            }

            $asset = app(MediaLibraryService::class)->syncUploadedFile(MediaAsset::DISK_PUBLIC, $path);

            if (! $asset) {
                $this->uploadError = 'Failed to register the file in the media library.';

                return;
            }

            if (! $this->multiple) {
                $this->pendingMediaIds = [$asset->id];
            } else {
                $this->pendingMediaIds[] = $asset->id;
            }

            $this->dispatch('pending-media-updated', collection: $this->collection, ids: $this->pendingMediaIds);
            $this->uploadFile = null;
            unset($this->pendingPreviews, $this->libraryAssets);

            if (! $this->multiple) {
                $this->modalOpen = false;
            }

            return;
        }

        try {
            $this->validate(['uploadFile' => 'required|file|mimes:jpg,jpeg,png,gif,webp,avif,bmp,ico|max:10240']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->uploadError = $e->validator->errors()->first();

            return;
        }

        if (! $this->multiple) {
            $this->record->clearMediaCollection($this->collection);
        }

        $ext          = strtolower($this->uploadFile->getClientOriginalExtension());
        $base         = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($this->uploadFile->getClientOriginalName(), PATHINFO_FILENAME));
        $originalName = ($base ?: 'upload') . '.' . $ext;

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
