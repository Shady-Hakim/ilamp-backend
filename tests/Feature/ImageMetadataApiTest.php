<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\MediaAsset;
use App\Models\PortfolioProject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImageMetadataApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_api_returns_library_image_metadata(): void
    {
        Storage::disk('public')->put('api-tests/blog/api-image.png', $this->pngFixture());

        $post = BlogPost::query()->create([
            'slug' => 'api-image-post',
            'title' => 'API Image Post',
            'excerpt' => 'Excerpt',
            'body' => 'Body',
            'author_name' => 'iLamp',
            'is_published' => true,
        ]);

        $post->addMedia(Storage::disk('public')->path('api-tests/blog/api-image.png'))
            ->preservingOriginal()
            ->toMediaCollection('image');

        $resolved = (string) $post->fresh()->image_resolved_url;

        MediaAsset::query()->create([
            'storage_disk' => MediaAsset::DISK_PUBLIC,
            'relative_path' => ltrim(Str::after(parse_url($resolved, PHP_URL_PATH) ?: '', '/storage/'), '/'),
            'filename' => basename(parse_url($resolved, PHP_URL_PATH) ?: 'api-image.png'),
            'title' => 'API Blog Image',
            'alt_text' => 'Custom blog alt text',
            'caption' => 'Blog image caption',
            'seo_title' => 'Blog image SEO title',
            'seo_description' => 'Blog image SEO description',
        ]);

        $response = $this->withServerVariables([
            'HTTP_HOST' => '127.0.0.1:8000',
            'HTTPS' => 'off',
        ])->getJson('/api/blog/posts')
            ->assertOk()
            ->assertJsonPath('0.imageMeta.altText', 'Custom blog alt text')
            ->assertJsonPath('0.imageMeta.seoTitle', 'Blog image SEO title')
            ->assertJsonPath('0.imageMeta.caption', 'Blog image caption');

        $this->assertStringContainsString('/storage/', (string) $response->json('0.imageMeta.url'));
    }

    public function test_portfolio_api_returns_library_image_metadata(): void
    {
        Storage::disk('public')->put('api-tests/portfolio/api-project.png', $this->pngFixture());
        Storage::disk('public')->put('api-tests/portfolio/api-logo.png', $this->pngFixture());
        Storage::disk('public')->put('api-tests/portfolio/api-gallery.png', $this->pngFixture());

        $project = PortfolioProject::query()->create([
            'slug' => 'api-portfolio-project',
            'title' => 'API Portfolio Project',
            'short_description' => 'Description',
            'is_published' => true,
        ]);

        $project->addMedia(Storage::disk('public')->path('api-tests/portfolio/api-project.png'))
            ->preservingOriginal()
            ->toMediaCollection('featured_image');

        $project->addMedia(Storage::disk('public')->path('api-tests/portfolio/api-logo.png'))
            ->preservingOriginal()
            ->toMediaCollection('client_logo');

        $project->addMedia(Storage::disk('public')->path('api-tests/portfolio/api-gallery.png'))
            ->preservingOriginal()
            ->toMediaCollection('gallery');

        $project = $project->fresh();

        $featuredPath = parse_url((string) $project->image_resolved_url, PHP_URL_PATH) ?: '';
        $logoPath = parse_url((string) $project->client_logo_resolved_url, PHP_URL_PATH) ?: '';
        $galleryPath = parse_url((string) ($project->gallery_resolved_urls[0] ?? ''), PHP_URL_PATH) ?: '';

        MediaAsset::query()->create([
            'storage_disk' => MediaAsset::DISK_PUBLIC,
            'relative_path' => ltrim(Str::after($featuredPath, '/storage/'), '/'),
            'filename' => basename($featuredPath),
            'alt_text' => 'Project image alt text',
        ]);

        MediaAsset::query()->create([
            'storage_disk' => MediaAsset::DISK_PUBLIC,
            'relative_path' => ltrim(Str::after($logoPath, '/storage/'), '/'),
            'filename' => basename($logoPath),
            'alt_text' => 'Client logo alt text',
        ]);

        MediaAsset::query()->create([
            'storage_disk' => MediaAsset::DISK_PUBLIC,
            'relative_path' => ltrim(Str::after($galleryPath, '/storage/'), '/'),
            'filename' => basename($galleryPath),
            'alt_text' => 'Gallery image alt text',
        ]);

        $response = $this->withServerVariables([
            'HTTP_HOST' => '127.0.0.1:8000',
            'HTTPS' => 'off',
        ])->getJson('/api/portfolio/projects')
            ->assertOk()
            ->assertJsonPath('0.imageMeta.altText', 'Project image alt text')
            ->assertJsonPath('0.clientLogoMeta.altText', 'Client logo alt text')
            ->assertJsonPath('0.galleryMeta.0.altText', 'Gallery image alt text');

        $this->assertStringContainsString('/storage/', (string) $response->json('0.imageMeta.url'));
        $this->assertStringContainsString('/storage/', (string) $response->json('0.clientLogoMeta.url'));
    }

    protected function pngFixture(): string
    {
        $image = imagecreatetruecolor(2, 2);
        $background = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $background);

        ob_start();
        imagepng($image);
        $binary = (string) ob_get_clean();

        imagedestroy($image);

        return $binary;
    }
}
