<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\PortfolioProject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicFrontendFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_serves_dynamic_portfolio_project_pages_for_published_database_slugs_without_exported_html(): void
    {
        if (! file_exists(public_path('portfolio/ecoshop-ecommerce/index.html'))) {
            $this->markTestSkipped('Published portfolio project template not found.');
        }

        $project = PortfolioProject::query()->create([
            'slug' => 'exodus-company-website',
            'title' => 'Exodus Company Website',
            'brief' => 'A modern corporate website for Exodus Company with CMS-managed case study content.',
            'short_description' => 'Corporate website redesign and delivery.',
            'is_published' => true,
        ]);

        $response = $this->get("/portfolio/{$project->slug}");

        $response->assertOk();
        $response->assertSee('Exodus Company Website');
        $response->assertSee('/portfolio/exodus-company-website/');
    }

    public function test_it_serves_dynamic_blog_post_pages_for_published_database_slugs_without_exported_html(): void
    {
        if (! file_exists(public_path('blog/ai-transforming-business-2024/index.html'))) {
            $this->markTestSkipped('Published blog post template not found.');
        }

        $post = BlogPost::query()->create([
            'slug' => 'test-new-blog',
            'title' => 'Test New Blog',
            'excerpt' => 'A test blog post created from the dashboard.',
            'body' => '<p>Test body content.</p>',
            'author_name' => 'iLamp',
            'is_published' => true,
        ]);

        $response = $this->get("/blog/{$post->slug}");

        $response->assertOk();
        $response->assertSee('Test New Blog');
        $response->assertSee('/blog/test-new-blog/');
    }
}
