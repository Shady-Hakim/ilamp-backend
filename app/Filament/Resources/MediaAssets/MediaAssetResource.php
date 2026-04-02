<?php

namespace App\Filament\Resources\MediaAssets;

use App\Filament\Resources\MediaAssets\Pages\ManageMediaAssets;
use App\Models\BlogPost;
use App\Models\MediaAsset;
use App\Models\PortfolioProject;
use App\Services\MediaLibraryService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class MediaAssetResource extends Resource
{
    protected static ?string $model = MediaAsset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Photo;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    /**
     * @var array<string, list<string>>|null
     */
    protected static ?array $attachmentMap = null;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return 'Library';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Library';
    }

    public static function getModelLabel(): string
    {
        return 'Library item';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Preview')
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('preview')
                            ->hiddenLabel()
                            ->content(fn (?MediaAsset $record): HtmlString => new HtmlString(self::renderPreview($record))),
                    ]),
                Section::make('File Details')
                    ->schema([
                        Placeholder::make('filename_info')
                            ->label('Filename')
                            ->content(fn (?MediaAsset $record): string => $record?->filename ?: '—'),
                        Placeholder::make('directory_info')
                            ->label('Directory')
                            ->content(fn (?MediaAsset $record): string => $record?->directory ?: '—'),
                        Placeholder::make('mime_type_info')
                            ->label('MIME type')
                            ->content(fn (?MediaAsset $record): string => $record?->mime_type ?: '—'),
                        Placeholder::make('storage_info')
                            ->label('Storage')
                            ->content(fn (?MediaAsset $record): string => $record?->storage_disk ?: '—'),
                        Placeholder::make('dimensions_info')
                            ->label('Dimensions')
                            ->content(fn (?MediaAsset $record): string => $record?->formatted_dimensions ?: '—'),
                        Placeholder::make('size_info')
                            ->label('File size')
                            ->content(fn (?MediaAsset $record): string => $record?->formatted_size ?: '—'),
                        Placeholder::make('relative_path_info')
                            ->label('Relative path')
                            ->content(fn (?MediaAsset $record): string => $record?->relative_path ?: '—')
                            ->columnSpanFull(),
                        Placeholder::make('attached_to_info')
                            ->label('Attached to')
                            ->content(fn (?MediaAsset $record): string => $record ? static::resolveAttachmentSummary($record) : 'Unattached')
                            ->columnSpanFull(),
                        Placeholder::make('public_url_info')
                            ->label('Public URL')
                            ->content(fn (?MediaAsset $record): HtmlString => new HtmlString(self::renderPublicUrl($record)))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('SEO')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')
                            ->helperText('Internal library title for this image.'),
                        TextInput::make('alt_text')
                            ->label('Alt text')
                            ->helperText('Describe the image for accessibility and search engines.'),
                        Textarea::make('caption')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('seo_title'),
                        Textarea::make('seo_description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('preview')
                    ->label('Image')
                    ->html()
                    ->state(fn (MediaAsset $record): string => self::renderThumb($record)),
                TextColumn::make('title')
                    ->searchable(['title', 'filename', 'alt_text', 'relative_path', 'seo_title'])
                    ->formatStateUsing(fn (?string $state, MediaAsset $record): string => $state ?: $record->filename)
                    ->description(fn (MediaAsset $record): string => $record->relative_path)
                    ->wrap(),
                TextColumn::make('attached_to')
                    ->label('Attached To')
                    ->state(fn (MediaAsset $record): string => static::resolveAttachmentSummary($record))
                    ->wrap(),
                TextColumn::make('directory')
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('formatted_dimensions')
                    ->label('Dimensions')
                    ->placeholder('—'),
                TextColumn::make('formatted_size')
                    ->label('File size')
                    ->placeholder('—'),
                TextColumn::make('updated_at')
                    ->since()
                    ->label('Updated'),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->label('Remove'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Remove selected'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMediaAssets::route('/'),
        ];
    }

    protected static function renderPreview(?MediaAsset $record): string
    {
        if (! $record?->url) {
            return '<div class="text-sm text-gray-500">No image available.</div>';
        }

        $alt = e($record->alt_text ?: $record->title ?: $record->filename);
        $url = e($record->url);

        return <<<HTML
<div style="display:flex;justify-content:center;padding:1rem 0;">
    <img src="{$url}" alt="{$alt}" style="max-width:100%;max-height:360px;border-radius:1rem;object-fit:contain;border:1px solid rgba(148,163,184,.35);background:#fff;padding:.75rem;">
</div>
HTML;
    }

    protected static function renderThumb(MediaAsset $record): string
    {
        $alt = e($record->alt_text ?: $record->title ?: $record->filename);
        $url = e($record->url);

        return <<<HTML
<img src="{$url}" alt="{$alt}" style="width:4rem;height:4rem;border-radius:.875rem;object-fit:cover;border:1px solid rgba(148,163,184,.35);background:#fff;padding:.125rem;">
HTML;
    }

    protected static function renderPublicUrl(?MediaAsset $record): string
    {
        if (! $record?->url) {
            return '—';
        }

        $url = e($record->url);

        return <<<HTML
<a href="{$url}" target="_blank" rel="noopener noreferrer" style="color:#2563eb;word-break:break-all;">{$url}</a>
HTML;
    }

    protected static function resolveAttachmentSummary(MediaAsset $record): string
    {
        $attachments = static::getAttachmentMap()[static::makeAttachmentKey(
            $record->storage_disk,
            $record->relative_path,
        )] ?? [];

        return $attachments === []
            ? 'Unattached'
            : implode(', ', $attachments);
    }

    /**
     * @return array<string, list<string>>
     */
    protected static function getAttachmentMap(): array
    {
        if (static::$attachmentMap !== null) {
            return static::$attachmentMap;
        }

        $service = app(MediaLibraryService::class);
        $map = [];

        BlogPost::query()
            ->select(['title', 'image_url'])
            ->whereNotNull('image_url')
            ->get()
            ->each(function (BlogPost $post) use (&$map, $service): void {
                static::pushAttachmentLabel(
                    $map,
                    $service,
                    $post->image_url,
                    "Post: {$post->title}",
                );
            });

        PortfolioProject::query()
            ->select(['title', 'image_url', 'client_logo_url', 'gallery'])
            ->get()
            ->each(function (PortfolioProject $project) use (&$map, $service): void {
                static::pushAttachmentLabel(
                    $map,
                    $service,
                    $project->image_url,
                    "Project: {$project->title}",
                );

                static::pushAttachmentLabel(
                    $map,
                    $service,
                    $project->client_logo_url,
                    "Project: {$project->title}",
                );

                foreach ($project->gallery ?? [] as $galleryImage) {
                    static::pushAttachmentLabel(
                        $map,
                        $service,
                        $galleryImage,
                        "Project: {$project->title}",
                    );
                }
            });

        static::$attachmentMap = collect($map)
            ->map(fn (array $labels): array => array_values(array_unique($labels)))
            ->all();

        return static::$attachmentMap;
    }

    /**
     * @param  array<string, list<string>>  $map
     */
    protected static function pushAttachmentLabel(
        array &$map,
        MediaLibraryService $service,
        ?string $reference,
        string $label,
    ): void {
        $normalized = $service->normalizeReference($reference);

        if (! $normalized) {
            return;
        }

        $key = static::makeAttachmentKey(
            $normalized['storage_disk'],
            $normalized['relative_path'],
        );

        $map[$key] ??= [];
        $map[$key][] = $label;
    }

    protected static function makeAttachmentKey(string $storageDisk, string $relativePath): string
    {
        return $storageDisk . '|' . $relativePath;
    }
}
