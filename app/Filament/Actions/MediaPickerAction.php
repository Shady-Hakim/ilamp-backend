<?php

namespace App\Filament\Actions;

use App\Models\MediaAsset;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class MediaPickerAction
{
    /**
     * Build a "Pick from Library" section action.
     *
     * @param  string  $collection  Spatie MediaLibrary collection name.
     * @param  string  $formField   Filament form field name (key in mountedActionsData).
     * @param  bool    $multiple    Whether multiple images can be picked at once.
     */
    public static function make(string $collection, string $formField, bool $multiple = false): Action
    {
        $selectField = Select::make('selected_ids')
            ->label($multiple ? 'Choose images from library' : 'Choose an image from library')
            ->options(fn (): array => static::buildOptions())
            ->searchable()
            ->required()
            ->noSearchResultsMessage('No images found. Use "Sync Library" in the Library section first.');

        if ($multiple) {
            $selectField = $selectField->multiple();
        }

        return Action::make('pick_from_library_' . preg_replace('/\W+/', '_', $collection))
            ->label('Pick from Library')
            ->icon(Heroicon::Photo)
            ->color('gray')
            ->modalWidth('3xl')
            ->modalHeading('Pick from Library')
            ->form([$selectField])
            ->action(function (array $data, ?Model $record, mixed $livewire) use ($collection, $formField, $multiple): void {
                if (! $record) {
                    Notification::make()
                        ->warning()
                        ->title('Save the record first, then use Pick from Library.')
                        ->send();

                    return;
                }

                $ids = $multiple
                    ? array_filter((array) ($data['selected_ids'] ?? []))
                    : array_filter([$data['selected_ids'] ?? null]);

                if (empty($ids)) {
                    return;
                }

                if (! $multiple) {
                    $record->clearMediaCollection($collection);

                    // Reset upload field state in the open edit form
                    if (isset($livewire->mountedActionsData[0]) && is_array($livewire->mountedActionsData[0])) {
                        $livewire->mountedActionsData[0][$formField] = [];
                    }
                }

                $added = 0;

                foreach ($ids as $assetId) {
                    $asset = MediaAsset::find($assetId);

                    if (! $asset || blank($asset->relative_path) || blank($asset->filename)) {
                        continue;
                    }

                    $absolutePath = $asset->storage_disk === MediaAsset::DISK_PUBLIC
                        ? storage_path('app/public/' . ltrim($asset->relative_path, '/'))
                        : public_path(ltrim($asset->relative_path, '/'));

                    if (! is_file($absolutePath)) {
                        continue;
                    }

                    try {
                        $newMedia = $record
                            ->addMedia($absolutePath)
                            ->preservingOriginal()
                            ->usingFileName($asset->filename)
                            ->toMediaCollection($collection);

                        // Update the SpatieMediaLibraryFileUpload field state so the
                        // newly picked image appears immediately without closing the form.
                        if (isset($livewire->mountedActionsData[0]) && is_array($livewire->mountedActionsData[0])) {
                            $livewire->mountedActionsData[0][$formField] ??= [];
                            $livewire->mountedActionsData[0][$formField][$newMedia->uuid] = $newMedia->getUrl();
                        }

                        $added++;
                    } catch (\Throwable) {
                        // Skip files that cannot be read or copied.
                    }
                }

                if ($added === 0) {
                    Notification::make()->warning()->title('No files could be added. The files may be missing on disk.')->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title($added . ($added === 1 ? ' image' : ' images') . ' added from library.')
                    ->send();
            });
    }

    /** @return array<int|string, string> */
    private static function buildOptions(): array
    {
        return MediaAsset::query()
            ->whereNotNull('relative_path')
            ->whereNotNull('filename')
            ->orderByDesc('updated_at')
            ->limit(500)
            ->get(['id', 'filename', 'title'])
            ->mapWithKeys(fn (MediaAsset $asset): array => [
                $asset->id => $asset->title ? "{$asset->title} ({$asset->filename})" : $asset->filename,
            ])
            ->all();
    }
}
