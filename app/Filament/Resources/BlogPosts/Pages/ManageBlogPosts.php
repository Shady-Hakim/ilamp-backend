<?php

namespace App\Filament\Resources\BlogPosts\Pages;

use App\Filament\Resources\BlogPosts\BlogPostResource;
use App\Models\MediaAsset;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;

class ManageBlogPosts extends ManageRecords
{
    protected static string $resource = BlogPostResource::class;

    /** Pending media queued by MediaCollectionPicker before the record is saved. */
    public array $pendingMedia = [];

    #[On('pending-media-updated')]
    public function capturePendingMedia(string $collection, array $ids): void
    {
        $this->pendingMedia[$collection] = $ids;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->after(function (Model $record) {
                    $this->attachPendingMedia($record);
                    $this->pendingMedia = [];
                }),
        ];
    }

    private function attachPendingMedia(Model $record): void
    {
        foreach ($this->pendingMedia as $collection => $ids) {
            foreach ($ids as $assetId) {
                $asset = MediaAsset::find($assetId);

                if (! $asset) {
                    continue;
                }

                $path = $asset->storage_disk === MediaAsset::DISK_PUBLIC
                    ? storage_path('app/public/'.ltrim($asset->relative_path, '/'))
                    : public_path(ltrim($asset->relative_path, '/'));

                if (! is_file($path)) {
                    continue;
                }

                $record->addMedia($path)
                    ->preservingOriginal()
                    ->usingFileName($asset->filename)
                    ->toMediaCollection($collection);
            }
        }
    }
}
