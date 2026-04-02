<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\MediaAsset;
use App\Models\PortfolioProject;
use App\Services\MediaLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Storage::disk('public')->deleteDirectory('library-test');
        File::deleteDirectory(public_path('uploads/library-test'));

        parent::tearDown();
    }

    public function test_it_syncs_local_public_and_legacy_upload_images_into_the_library(): void
    {
        Storage::disk('public')->put('library-test/storage-image.png', $this->pngFixture());

        File::ensureDirectoryExists(public_path('uploads/library-test'));
        File::put(public_path('uploads/library-test/upload-image.png'), $this->pngFixture());

        $count = app(MediaLibraryService::class)->syncAll();

        $this->assertGreaterThanOrEqual(2, $count);
        $this->assertDatabaseHas('media_assets', [
            'storage_disk' => MediaAsset::DISK_PUBLIC,
            'relative_path' => 'library-test/storage-image.png',
        ]);
        $this->assertDatabaseHas('media_assets', [
            'storage_disk' => MediaAsset::DISK_PUBLIC_PATH,
            'relative_path' => 'uploads/library-test/upload-image.png',
        ]);
    }

    public function test_deleting_a_library_asset_removes_the_file_and_clears_content_references(): void
    {
        Storage::disk('public')->put('library-test/delete-me.png', $this->pngFixture());

        $blogPost = BlogPost::query()->create([
            'slug' => 'library-test-post',
            'title' => 'Library Test Post',
            'image_url' => 'library-test/delete-me.png',
        ]);

        $project = PortfolioProject::query()->create([
            'slug' => 'library-test-project',
            'title' => 'Library Test Project',
            'image_url' => 'library-test/delete-me.png',
            'gallery' => ['library-test/delete-me.png'],
        ]);

        app(MediaLibraryService::class)->syncReferencedFiles([
            $blogPost->image_url,
            $project->image_url,
            ...($project->gallery ?? []),
        ]);

        $asset = MediaAsset::query()->where([
            'storage_disk' => MediaAsset::DISK_PUBLIC,
            'relative_path' => 'library-test/delete-me.png',
        ])->firstOrFail();

        $asset->delete();

        $this->assertFalse(Storage::disk('public')->exists('library-test/delete-me.png'));
        $this->assertNull($blogPost->fresh()->image_url);
        $this->assertNull($project->fresh()->image_url);
        $this->assertSame([], $project->fresh()->gallery);
    }

    public function test_sync_preserves_existing_library_seo_fields(): void
    {
        Storage::disk('public')->put('library-test/keep-seo.png', $this->pngFixture());

        app(MediaLibraryService::class)->syncAll();

        $asset = MediaAsset::query()->where([
            'storage_disk' => MediaAsset::DISK_PUBLIC,
            'relative_path' => 'library-test/keep-seo.png',
        ])->firstOrFail();

        $asset->update([
            'title' => 'Homepage Hero Image',
            'alt_text' => 'A professional consultation meeting',
            'seo_title' => 'Consultation hero image',
            'seo_description' => 'Main visual used in the homepage hero section.',
        ]);

        app(MediaLibraryService::class)->syncAll();

        $asset = $asset->fresh();

        $this->assertSame('Homepage Hero Image', $asset->title);
        $this->assertSame('A professional consultation meeting', $asset->alt_text);
        $this->assertSame('Consultation hero image', $asset->seo_title);
        $this->assertSame('Main visual used in the homepage hero section.', $asset->seo_description);
    }

    protected function pngFixture(): string
    {
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8B2eQAAAAASUVORK5CYII=',
            true,
        );
    }
}
