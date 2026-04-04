<?php

namespace App\Filament\Resources\MediaAssets\Pages;

use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Models\MediaAsset;
use App\Services\MediaLibraryService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;

class ManageMediaAssets extends ManageRecords
{
    protected static string $resource = MediaAssetResource::class;

    #[Url(as: 'view')]
    public string $libraryView = 'grid';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadImages')
                ->label('Upload Images')
                ->icon(Heroicon::ArrowUpTray)
                ->modalWidth('2xl')
                ->modalHeading('Upload Images to Library')
                ->form([
                    FileUpload::make('files')
                        ->label('Images')
                        ->multiple()
                        ->image()
                        ->maxSize(10240)
                        ->disk('public')
                        ->directory('library')
                        ->required(),
                ])
                ->action(function (array $data, MediaLibraryService $service): void {
                    $paths = array_filter((array) ($data['files'] ?? []));
                    $count = 0;

                    foreach ($paths as $relativePath) {
                        if ($service->syncUploadedFile(MediaAsset::DISK_PUBLIC, $relativePath)) {
                            $count++;
                        }
                    }

                    Notification::make()
                        ->success()
                        ->title("{$count} image(s) uploaded and added to the library.")
                        ->send();
                }),
            Action::make('toggleLibraryView')
                ->label(fn (): string => $this->isGridView() ? 'Table View' : 'Grid View')
                ->icon(fn (): Heroicon => $this->isGridView() ? Heroicon::TableCells : Heroicon::Photo)
                ->color('gray')
                ->action(function (): void {
                    $this->libraryView = $this->isGridView() ? 'table' : 'grid';
                    $this->resetTable();
                }),
            Action::make('syncLibrary')
                ->label('Sync Library')
                ->icon(Heroicon::ArrowPath)
                ->action(function (MediaLibraryService $mediaLibraryService): void {
                    $count = $mediaLibraryService->syncAll();

                    Notification::make()
                        ->success()
                        ->title('Library synced.')
                        ->body("{$count} image file(s) are now indexed in the library.")
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        $table = parent::table($table);

        if (! $this->isGridView()) {
            return $table->content(null);
        }

        return $table->content(fn () => view('filament.media-assets.grid-view'));
    }

    protected function isGridView(): bool
    {
        return $this->libraryView === 'grid';
    }
}
