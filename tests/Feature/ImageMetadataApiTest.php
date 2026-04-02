<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\MediaAsset;
use App\Models\PortfolioProject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImageMetadataApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_api_returns_library_image_metadata(): void
    {
        MediaAsset::query()->create([
            'storage_disk' => MediaAsset::DISK_PUBLIC_PATH,
            'relative_path' => 'uploads/blog/api-image.png',
            'filename' => 'api-image.png',
            'title' => 'API Blog Image',
            'alt_text' => 'Custom blog alt text',
            'caption' => 'Blog image caption',
            'seo_title' => 'Blog image SEO title',
            'seo_description' => 'Blog image SEO description',
        ]);

        BlogPost::query()->create([
            'slug' => 'api-image-post',
            'title' => 'API Image Post',
            'excerpt' => 'Excerpt',
            'body' => 'Body',
            'author_name' => 'iLamp',
            'image_url' => '/uploads/blog/api-image.png',
            'is_published' => true,
        ]);

        $response = $this->withServerVariables([
            'HTTP_HOST' => '127.0.0.1:8000',
            'HTTPS' => 'off',
        ])->getJson('/api/blog/posts')
            ->assertOk()
            ->assertJsonPath('0.imageMeta.altText', 'Custom blog alt text')
            ->assertJsonPath('0.imageMeta.seoTitle', 'Blog image SEO title')
            ->assertJsonPath('0.imageMeta.caption', 'Blog image caption');

        $this->assertStringEndsWith(
            '/uploads/blog/api-image.png',
            (string) $response->json('0.imageMeta.url'),
        );
    }

    public function test_portfolio_api_returns_library_image_metadata(): void
    {
        MediaAsset::query()->create([
            'storage_disk' => MediaAsset::DISK_PUBLIC_PATH,
            'relative_path' => 'uploads/portfolio/api-project.png',
            'filename' => 'api-project.png',
            'alt_text' => 'Project image alt text',
        ]);

        MediaAsset::query()->create([
            'storage_disk' => MediaAsset::DISK_PUBLIC_PATH,
            'relative_path' => 'uploads/portfolio/api-logo.png',
            'filename' => 'api-logo.png',
            'alt_text' => 'Client logo alt text',
        ]);

        MediaAsset::query()->create([
            'storage_disk' => MediaAsset::DISK_PUBLIC_PATH,
            'relative_path' => 'uploads/portfolio/api-gallery.png',
            'filename' => 'api-gallery.png',
            'alt_text' => 'Gallery image alt text',
        ]);

        PortfolioProject::query()->create([
            'slug' => 'api-portfolio-project',
            'title' => 'API Portfolio Project',
            'short_description' => 'Description',
            'image_url' => '/uploads/portfolio/api-project.png',
            'client_logo_url' => '/uploads/portfolio/api-logo.png',
            'gallery' => ['/uploads/portfolio/api-gallery.png'],
            'is_published' => true,
        ]);

        $response = $this->withServerVariables([
            'HTTP_HOST' => '127.0.0.1:8000',
            'HTTPS' => 'off',
        ])->getJson('/api/portfolio/projects')
            ->assertOk()
            ->assertJsonPath('0.imageMeta.altText', 'Project image alt text')
            ->assertJsonPath('0.clientLogoMeta.altText', 'Client logo alt text')
            ->assertJsonPath('0.galleryMeta.0.altText', 'Gallery image alt text');

        $this->assertStringEndsWith(
            '/uploads/portfolio/api-project.png',
            (string) $response->json('0.imageMeta.url'),
        );
        $this->assertStringEndsWith(
            '/uploads/portfolio/api-logo.png',
            (string) $response->json('0.clientLogoMeta.url'),
        );
    }
}
