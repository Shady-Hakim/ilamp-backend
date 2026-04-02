<?php

namespace App\Support;

use App\Models\MediaAsset;
use App\Services\MediaLibraryService;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Support\Enums\GridDirection;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class MediaLibraryPicker extends Field
{
    protected string $view = 'filament.media-library-picker';

    protected bool | Closure $isMultiple = false;

    protected string | Closure | null $uploadDisk = 'public';

    protected string | Closure | null $uploadDirectory = null;

    protected string | Closure | null $uploadVisibility = 'public';

    protected bool | Closure $shouldPreserveUploadFilenames = true;

    protected bool | Closure $hasUploadImageEditor = true;

    protected int | Closure | null $previewLimit = 4;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerActions([
            fn (MediaLibraryPicker $component): Action => $component->getOpenLibraryAction(),
            fn (MediaLibraryPicker $component): Action => $component->getClearSelectionAction(),
        ]);
    }

    public function multiple(bool | Closure $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function uploadDisk(string | Closure | null $disk): static
    {
        $this->uploadDisk = $disk;

        return $this;
    }

    public function uploadDirectory(string | Closure | null $directory): static
    {
        $this->uploadDirectory = $directory;

        return $this;
    }

    public function uploadVisibility(string | Closure | null $visibility): static
    {
        $this->uploadVisibility = $visibility;

        return $this;
    }

    public function preserveUploadFilenames(bool | Closure $condition = true): static
    {
        $this->shouldPreserveUploadFilenames = $condition;

        return $this;
    }

    public function uploadImageEditor(bool | Closure $condition = true): static
    {
        $this->hasUploadImageEditor = $condition;

        return $this;
    }

    public function previewLimit(int | Closure | null $limit): static
    {
        $this->previewLimit = $limit;

        return $this;
    }

    public function isMultiple(): bool
    {
        return (bool) $this->evaluate($this->isMultiple);
    }

    public function getUploadDisk(): ?string
    {
        return $this->evaluate($this->uploadDisk);
    }

    public function getUploadDirectory(): ?string
    {
        return $this->evaluate($this->uploadDirectory);
    }

    public function getUploadVisibility(): ?string
    {
        return $this->evaluate($this->uploadVisibility);
    }

    public function shouldPreserveUploadFilenames(): bool
    {
        return (bool) $this->evaluate($this->shouldPreserveUploadFilenames);
    }

    public function hasUploadImageEditor(): bool
    {
        return (bool) $this->evaluate($this->hasUploadImageEditor);
    }

    public function getPreviewLimit(): ?int
    {
        return $this->evaluate($this->previewLimit);
    }

    public function hasSelection(): bool
    {
        return $this->getSelectionCount() > 0;
    }

    public function getSelectionCount(): int
    {
        return count($this->normalizeStoredReferences($this->getState()));
    }

    public function getPreviewOverflowCount(): int
    {
        $limit = $this->getPreviewLimit();

        if ($limit === null) {
            return 0;
        }

        return max($this->getSelectionCount() - $limit, 0);
    }

    /**
     * @return array<int, array{url: string, title: string, subtitle: string}>
     */
    public function getPreviewItems(): array
    {
        $references = $this->normalizeStoredReferences($this->getState());
        $limit = $this->getPreviewLimit();

        if ($limit !== null) {
            $references = array_slice($references, 0, $limit);
        }

        $service = app(MediaLibraryService::class);
        $items = [];

        foreach ($references as $reference) {
            $url = $service->toAssetUrl($reference);

            if (! $url) {
                continue;
            }

            $asset = $service->findByReference($reference);
            $path = parse_url($url, PHP_URL_PATH) ?: $reference;

            $items[] = [
                'url' => $url,
                'title' => $asset?->title ?: basename($path),
                'subtitle' => $asset?->directory ?: $reference,
            ];
        }

        return $items;
    }

    public function getOpenLibraryAction(): Action
    {
        $uploadField = FileUpload::make('uploads')
            ->label($this->isMultiple() ? 'Upload new images' : 'Upload new image')
            ->image()
            ->multiple($this->isMultiple())
            ->appendFiles($this->isMultiple())
            ->disk($this->getUploadDisk())
            ->directory($this->getUploadDirectory())
            ->visibility($this->getUploadVisibility())
            ->preserveFilenames($this->shouldPreserveUploadFilenames())
            ->fetchFileInformation(false)
            ->columnSpanFull();

        if ($this->isMultiple()) {
            $uploadField
                ->panelLayout('grid')
                ->itemPanelAspectRatio('1:1')
                ->imagePreviewHeight('180');
        } elseif ($this->hasUploadImageEditor()) {
            $uploadField->imageEditor();
        }

        return Action::make('openLibrary')
            ->label('Open Image Library')
            ->button()
            ->color('gray')
            ->icon(Heroicon::Photo)
            ->extraAttributes([
                'style' => 'width:100%;justify-content:center;',
            ])
            ->slideOver()
            ->modalWidth(Width::SevenExtraLarge)
            ->modalHeading('Image Library')
            ->modalDescription(
                $this->isMultiple()
                    ? 'Choose existing images from the library or upload new ones from your computer.'
                    : 'Choose an existing image from the library or upload a new one from your computer.',
            )
            ->modalSubmitActionLabel(
                $this->isMultiple() ? 'Use Selected Images' : 'Use Selected Image',
            )
            ->fillForm(fn (): array => [
                'selection' => $this->getSelectedLibraryAssetIds(),
                'uploads' => [],
            ])
            ->schema([
                $uploadField,
                Placeholder::make('library_intro')
                    ->hiddenLabel()
                    ->content(
                        $this->hasLibraryItems()
                            ? 'Selected uploads are added to the library automatically when you save this modal.'
                            : 'The library is empty right now. Upload an image below to add it and select it immediately.',
                    )
                    ->columnSpanFull(),
                CheckboxList::make('selection')
                    ->label('Library')
                    ->hiddenLabel()
                    ->view('filament.media-library-checkbox-list')
                    ->options(fn (): array => $this->getLibrarySelectionOptions())
                    ->allowHtml()
                    ->searchable()
                    ->bulkToggleable($this->isMultiple())
                    ->columns(4)
                    ->gridDirection(GridDirection::Row)
                    ->maxItems($this->isMultiple() ? null : 1)
                    ->helperText($this->isMultiple() ? 'Select one or more images.' : 'Select one image.')
                    ->columnSpanFull(),
            ])
            ->action(function (array $data, MediaLibraryService $mediaLibraryService): void {
                $currentReferences = $this->normalizeStoredReferences($this->getState());
                $selectedReferences = $this->resolveSelectedReferences($data['selection'] ?? []);
                $uploadedReferences = $this->normalizeUploadedReferences($data['uploads'] ?? null);

                if ($uploadedReferences !== []) {
                    $mediaLibraryService->syncReferencedFiles($uploadedReferences);
                }

                if ($this->isMultiple()) {
                    $this->state(
                        $this->buildMultipleState(
                            $currentReferences,
                            $selectedReferences,
                            $uploadedReferences,
                            $mediaLibraryService,
                        ),
                    )->callAfterStateUpdated();

                    return;
                }

                $this->state(
                    $uploadedReferences[0] ?? $selectedReferences[0] ?? $currentReferences[0] ?? null,
                )->callAfterStateUpdated();
            });
    }

    public function getClearSelectionAction(): Action
    {
        return Action::make('clearSelection')
            ->label('Clear')
            ->link()
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (): bool => $this->hasSelection())
            ->action(function (): void {
                $this->state($this->isMultiple() ? [] : null)->callAfterStateUpdated();
            });
    }

    protected function hasLibraryItems(): bool
    {
        return MediaAsset::query()->exists();
    }

    /**
     * @return array<int, string>
     */
    protected function getSelectedLibraryAssetIds(): array
    {
        $service = app(MediaLibraryService::class);
        $selectedIds = [];

        foreach ($this->normalizeStoredReferences($this->getState()) as $reference) {
            $asset = $service->findByReference($reference);

            if (! $asset) {
                continue;
            }

            $selectedIds[] = (string) $asset->getKey();
        }

        return array_values(array_unique($selectedIds));
    }

    /**
     * @return array<string, HtmlString>
     */
    protected function getLibrarySelectionOptions(): array
    {
        return MediaAsset::query()
            ->orderByDesc('updated_at')
            ->get()
            ->mapWithKeys(fn (MediaAsset $asset): array => [
                (string) $asset->getKey() => $this->renderLibraryOption($asset),
            ])
            ->all();
    }

    protected function renderLibraryOption(MediaAsset $asset): HtmlString
    {
        $title = e($asset->title ?: $asset->filename);
        $subtitle = e($asset->directory ?: $asset->relative_path);
        $alt = e($asset->alt_text ?: $asset->title ?: $asset->filename);
        $url = e($asset->url);

        return new HtmlString(<<<HTML
<div style="display:flex;flex-direction:column;gap:.75rem;min-width:0;height:100%;min-height:18rem;">
    <div style="aspect-ratio:1 / 1;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:1rem;border:1px solid rgba(148,163,184,.35);background:#fff;padding:.5rem;flex-shrink:0;">
        <img src="{$url}" alt="{$alt}" style="max-width:100%;max-height:100%;object-fit:contain;">
    </div>
    <div style="min-width:0;display:flex;flex-direction:column;justify-content:flex-start;gap:.2rem;min-height:3rem;">
        <div style="font-size:.9rem;font-weight:600;line-height:1.35;min-height:2.45rem;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
            {$title}
        </div>
        <div style="color:#6b7280;font-size:.78rem;line-height:1.25;min-height:1rem;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
            {$subtitle}
        </div>
    </div>
</div>
HTML);
    }

    /**
     * @param  array<int, mixed>|mixed  $selectedAssetIds
     * @return array<int, string>
     */
    protected function resolveSelectedReferences(mixed $selectedAssetIds): array
    {
        $selectedAssetIds = array_values(array_filter(array_map(
            static fn (mixed $id): string => trim((string) $id),
            Arr::wrap($selectedAssetIds),
        )));

        if ($selectedAssetIds === []) {
            return [];
        }

        $assets = MediaAsset::query()
            ->whereKey($selectedAssetIds)
            ->get()
            ->keyBy(fn (MediaAsset $asset): string => (string) $asset->getKey());

        $references = [];

        foreach ($selectedAssetIds as $selectedAssetId) {
            /** @var MediaAsset|null $asset */
            $asset = $assets->get($selectedAssetId);

            if (! $asset) {
                continue;
            }

            $references[] = $this->toStoredReference($asset);
        }

        return array_values(array_unique($references));
    }

    /**
     * @return array<int, string>
     */
    protected function normalizeStoredReferences(mixed $state): array
    {
        return array_values(array_filter(array_map(
            static fn (mixed $reference): string => trim((string) $reference),
            Arr::wrap($state),
        )));
    }

    /**
     * @return array<int, string>
     */
    protected function normalizeUploadedReferences(mixed $state): array
    {
        return array_values(array_filter(array_map(
            static fn (mixed $reference): string => trim((string) $reference),
            Arr::wrap($state),
        )));
    }

    protected function toStoredReference(MediaAsset $asset): string
    {
        if ($asset->storage_disk === MediaAsset::DISK_PUBLIC) {
            return ltrim($asset->relative_path, '/');
        }

        return '/' . ltrim($asset->relative_path, '/');
    }

    /**
     * @param  array<int, string>  $currentReferences
     * @param  array<int, string>  $selectedReferences
     * @param  array<int, string>  $uploadedReferences
     * @return array<int, string>
     */
    protected function buildMultipleState(
        array $currentReferences,
        array $selectedReferences,
        array $uploadedReferences,
        MediaLibraryService $mediaLibraryService,
    ): array {
        if (($selectedReferences === []) && ($uploadedReferences === [])) {
            return $currentReferences;
        }

        $resolvedReferences = [];

        foreach ($currentReferences as $reference) {
            if ($mediaLibraryService->findByReference($reference) === null) {
                $resolvedReferences[] = $reference;
            }
        }

        foreach ($currentReferences as $reference) {
            if (in_array($reference, $selectedReferences, true) && ! in_array($reference, $resolvedReferences, true)) {
                $resolvedReferences[] = $reference;
            }
        }

        foreach ([$selectedReferences, $uploadedReferences] as $referenceGroup) {
            foreach ($referenceGroup as $reference) {
                if (! in_array($reference, $resolvedReferences, true)) {
                    $resolvedReferences[] = $reference;
                }
            }
        }

        return $resolvedReferences;
    }
}
