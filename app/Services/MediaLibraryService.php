<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\MediaAsset;
use App\Models\PortfolioProject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SplFileInfo;

class MediaLibraryService
{
    /**
     * @var array<string, MediaAsset|null>
     */
    protected array $assetCache = [];

    /**
     * @var list<string>
     */
    protected const IMAGE_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'svg',
        'avif',
        'bmp',
        'ico',
    ];

    public function syncAll(): int
    {
        $syncedAt = now();
        $count = 0;

        foreach ($this->storageDiskFiles() as $relativePath) {
            if ($this->syncLocalAsset(MediaAsset::DISK_PUBLIC, $relativePath, $syncedAt)) {
                $count++;
            }
        }

        foreach ($this->publicUploadsFiles() as $relativePath) {
            if ($this->syncLocalAsset(MediaAsset::DISK_PUBLIC_PATH, $relativePath, $syncedAt)) {
                $count++;
            }
        }

        MediaAsset::query()
            ->whereIn('storage_disk', [MediaAsset::DISK_PUBLIC, MediaAsset::DISK_PUBLIC_PATH])
            ->where(function (Builder $query) use ($syncedAt): void {
                $query
                    ->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<', $syncedAt);
            })
            ->get()
            ->each(fn (MediaAsset $asset) => $asset->delete());

        return $count;
    }

    public function syncBlogPost(BlogPost $blogPost): int
    {
        return $this->syncReferencedFiles([$blogPost->image_url]);
    }

    public function syncPortfolioProject(PortfolioProject $project): int
    {
        return $this->syncReferencedFiles([
            $project->image_url,
            $project->client_logo_url,
            ...($project->gallery ?? []),
        ]);
    }

    /**
     * @param  array<int, string|null>  $references
     */
    public function syncReferencedFiles(array $references): int
    {
        $count = 0;

        foreach ($references as $reference) {
            $normalized = $this->normalizeReference($reference);

            if (! $normalized) {
                continue;
            }

            if ($this->syncLocalAsset($normalized['storage_disk'], $normalized['relative_path'])) {
                $count++;
            }
        }

        return $count;
    }

    public function deletePhysicalFile(MediaAsset $asset): void
    {
        if ($asset->storage_disk === MediaAsset::DISK_PUBLIC) {
            Storage::disk(MediaAsset::DISK_PUBLIC)->delete($asset->relative_path);

            return;
        }

        if ($asset->storage_disk === MediaAsset::DISK_PUBLIC_PATH) {
            File::delete(public_path($asset->relative_path));
        }
    }

    public function toAssetUrl(?string $value, ?string $origin = null): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $origin = filled($origin) ? rtrim((string) $origin, '/') : null;

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            if (! $origin) {
                return $value;
            }

            $normalized = $this->normalizeReference($value);
            $path = parse_url($value, PHP_URL_PATH);
            $query = parse_url($value, PHP_URL_QUERY);

            if (! $normalized || ! is_string($path) || $path === '') {
                return $value;
            }

            return $origin.$path.($query ? "?{$query}" : '');
        }

        if (str_starts_with($value, '/')) {
            return ($origin ?? '').$value;
        }

        if (Storage::disk(MediaAsset::DISK_PUBLIC)->exists($value)) {
            $storageUrl = Storage::disk(MediaAsset::DISK_PUBLIC)->url($value);

            if (! is_string($storageUrl)) {
                return null;
            }

            if (str_starts_with($storageUrl, 'http://') || str_starts_with($storageUrl, 'https://')) {
                return $origin
                    ? preg_replace('#^https?://[^/]+#', $origin, $storageUrl) ?? $storageUrl
                    : $storageUrl;
            }

            return ($origin ?? '').$storageUrl;
        }

        $path = '/'.ltrim($value, '/');

        return ($origin ?? '').$path;
    }

    public function findByReference(?string $reference): ?MediaAsset
    {
        $normalized = $this->normalizeReference($reference);

        if (! $normalized) {
            return null;
        }

        $cacheKey = $this->makeCacheKey(
            $normalized['storage_disk'],
            $normalized['relative_path'],
        );

        if (array_key_exists($cacheKey, $this->assetCache)) {
            return $this->assetCache[$cacheKey];
        }

        return $this->assetCache[$cacheKey] = MediaAsset::query()
            ->where('storage_disk', $normalized['storage_disk'])
            ->where('relative_path', $normalized['relative_path'])
            ->first();
    }

    /**
     * @return array{url: string, title: string|null, altText: string|null, caption: string|null, seoTitle: string|null, seoDescription: string|null}|null
     */
    public function toApiMedia(?string $reference, ?string $origin = null): ?array
    {
        $url = $this->toAssetUrl($reference, $origin);

        if (! $url) {
            return null;
        }

        $asset = $this->findByReference($reference);

        return [
            'url' => $url,
            'title' => $asset?->title,
            'altText' => $asset?->alt_text,
            'caption' => $asset?->caption,
            'seoTitle' => $asset?->seo_title,
            'seoDescription' => $asset?->seo_description,
        ];
    }

    public function clearAssetReferences(MediaAsset $asset): void
    {
        BlogPost::query()
            ->select(['id', 'image_url'])
            ->get()
            ->each(function (BlogPost $post) use ($asset): void {
                if (! $this->referenceMatchesAsset($post->image_url, $asset)) {
                    return;
                }

                BlogPost::query()
                    ->whereKey($post->getKey())
                    ->update(['image_url' => null]);
            });

        PortfolioProject::query()
            ->select(['id', 'image_url', 'client_logo_url', 'gallery'])
            ->get()
            ->each(function (PortfolioProject $project) use ($asset): void {
                $updates = [];

                if ($this->referenceMatchesAsset($project->image_url, $asset)) {
                    $updates['image_url'] = null;
                }

                if ($this->referenceMatchesAsset($project->client_logo_url, $asset)) {
                    $updates['client_logo_url'] = null;
                }

                $existingGallery = array_values($project->gallery ?? []);
                $filteredGallery = array_values(array_filter(
                    $existingGallery,
                    fn (?string $reference): bool => ! $this->referenceMatchesAsset($reference, $asset),
                ));

                if ($filteredGallery !== $existingGallery) {
                    $updates['gallery'] = $filteredGallery;
                }

                if ($updates === []) {
                    return;
                }

                PortfolioProject::query()
                    ->whereKey($project->getKey())
                    ->update($updates);
            });
    }

    /**
     * @return array{storage_disk: string, relative_path: string}|null
     */
    public function normalizeReference(?string $reference): ?array
    {
        $reference = trim((string) $reference);

        if ($reference === '') {
            return null;
        }

        if (filter_var($reference, FILTER_VALIDATE_URL)) {
            $referencePath = parse_url($reference, PHP_URL_PATH);

            if (! is_string($referencePath)) {
                return null;
            }

            $reference = $referencePath;
        }

        $reference = str_replace('\\', '/', $reference);
        $reference = preg_replace('#/+#', '/', $reference) ?? $reference;
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        if (str_starts_with($reference, '/storage/')) {
            return [
                'storage_disk' => MediaAsset::DISK_PUBLIC,
                'relative_path' => ltrim(Str::after($reference, '/storage/'), '/'),
            ];
        }

        if (str_starts_with($reference, 'storage/')) {
            return [
                'storage_disk' => MediaAsset::DISK_PUBLIC,
                'relative_path' => ltrim(Str::after($reference, 'storage/'), '/'),
            ];
        }

        if (str_starts_with($reference, '/uploads/')) {
            return [
                'storage_disk' => MediaAsset::DISK_PUBLIC_PATH,
                'relative_path' => ltrim($reference, '/'),
            ];
        }

        if (str_starts_with($reference, 'uploads/')) {
            return [
                'storage_disk' => MediaAsset::DISK_PUBLIC_PATH,
                'relative_path' => $reference,
            ];
        }

        if (! str_starts_with($reference, '/')) {
            return [
                'storage_disk' => MediaAsset::DISK_PUBLIC,
                'relative_path' => ltrim($reference, '/'),
            ];
        }

        return null;
    }

    protected function syncLocalAsset(string $storageDisk, string $relativePath, $syncedAt = null): ?MediaAsset
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        if ($relativePath === '' || ! $this->isImagePath($relativePath) || ! $this->fileExists($storageDisk, $relativePath)) {
            return null;
        }

        $metadata = $this->buildMetadata($storageDisk, $relativePath);

        $asset = MediaAsset::query()->firstOrNew([
            'storage_disk' => $storageDisk,
            'relative_path' => $relativePath,
        ]);

        $asset->fill([
            'directory' => $metadata['directory'],
            'filename' => $metadata['filename'],
            'extension' => $metadata['extension'],
            'mime_type' => $metadata['mime_type'],
            'size' => $metadata['size'],
            'width' => $metadata['width'],
            'height' => $metadata['height'],
            'last_synced_at' => $syncedAt ?? now(),
        ]);

        if (! $asset->exists || blank($asset->title)) {
            $asset->title = Str::headline(pathinfo($metadata['filename'], PATHINFO_FILENAME));
        }

        $asset->save();

        return $asset;
    }

    /**
     * @return array{directory: string|null, filename: string, extension: string|null, mime_type: string|null, size: int|null, width: int|null, height: int|null}
     */
    protected function buildMetadata(string $storageDisk, string $relativePath): array
    {
        $absolutePath = $this->absolutePath($storageDisk, $relativePath);
        $directory = dirname($relativePath);
        $imageSize = @getimagesize($absolutePath) ?: null;

        return [
            'directory' => $directory === '.' ? null : str_replace('\\', '/', $directory),
            'filename' => basename($relativePath),
            'extension' => pathinfo($relativePath, PATHINFO_EXTENSION) ?: null,
            'mime_type' => is_file($absolutePath) ? (@mime_content_type($absolutePath) ?: null) : null,
            'size' => is_file($absolutePath) ? File::size($absolutePath) : null,
            'width' => is_array($imageSize) && isset($imageSize[0]) ? (int) $imageSize[0] : null,
            'height' => is_array($imageSize) && isset($imageSize[1]) ? (int) $imageSize[1] : null,
        ];
    }

    protected function absolutePath(string $storageDisk, string $relativePath): string
    {
        if ($storageDisk === MediaAsset::DISK_PUBLIC) {
            return Storage::disk(MediaAsset::DISK_PUBLIC)->path($relativePath);
        }

        return public_path($relativePath);
    }

    protected function fileExists(string $storageDisk, string $relativePath): bool
    {
        if ($storageDisk === MediaAsset::DISK_PUBLIC) {
            return Storage::disk(MediaAsset::DISK_PUBLIC)->exists($relativePath);
        }

        return is_file(public_path($relativePath));
    }

    protected function isImagePath(string $path): bool
    {
        return in_array(Str::lower(pathinfo($path, PATHINFO_EXTENSION)), self::IMAGE_EXTENSIONS, true);
    }

    protected function referenceMatchesAsset(?string $reference, MediaAsset $asset): bool
    {
        $normalized = $this->normalizeReference($reference);

        return $normalized !== null
            && $normalized['storage_disk'] === $asset->storage_disk
            && $normalized['relative_path'] === $asset->relative_path;
    }

    /**
     * @return Collection<int, string>
     */
    protected function storageDiskFiles(): Collection
    {
        return collect(Storage::disk(MediaAsset::DISK_PUBLIC)->allFiles())
            ->filter(fn (string $path): bool => $this->isImagePath($path))
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    protected function publicUploadsFiles(): Collection
    {
        $uploadsDirectory = public_path('uploads');

        if (! File::isDirectory($uploadsDirectory)) {
            return collect();
        }

        return collect(File::allFiles($uploadsDirectory))
            ->map(function (SplFileInfo $file): string {
                $relativePath = Str::after($file->getPathname(), public_path() . DIRECTORY_SEPARATOR);

                return str_replace('\\', '/', $relativePath);
            })
            ->filter(fn (string $path): bool => $this->isImagePath($path))
            ->values();
    }

    protected function makeCacheKey(string $storageDisk, string $relativePath): string
    {
        return $storageDisk . '|' . $relativePath;
    }
}
