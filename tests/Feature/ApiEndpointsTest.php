<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\ContactMessage;
use App\Models\PortfolioProject;
use App\Services\ConsultationAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_settings_endpoint_returns_seeded_settings(): void
    {
        $this->seed();

        $this->getJson('/api/site-settings')
            ->assertOk()
            ->assertJsonPath('siteName', 'iLamp Agency')
            ->assertJsonPath('contactEmail', 'info@ilampagency.com')
            ->assertJsonPath('whatsappNumber', '+1234567890')
            ->assertJsonPath('whatsappUrl', 'https://wa.me/1234567890')
            ->assertJsonPath('socialLinks.0.icon', 'linkedin');
    }

    public function test_page_endpoint_returns_sections(): void
    {
        $this->seed();

        $this->getJson('/api/pages/services')
            ->assertOk()
            ->assertJsonPath('slug', 'services')
            ->assertJsonCount(5, 'sections');
    }

    public function test_blog_post_endpoint_falls_back_to_default_author_when_missing(): void
    {
        $this->seed();

        $post = BlogPost::query()->firstOrFail();
        $post->update([
            'author_name' => null,
        ]);

        $this->getJson("/api/blog/posts/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('author', 'iLamp Team');
    }

    public function test_blog_post_excerpt_is_generated_from_body(): void
    {
        $post = BlogPost::query()->create([
            'slug' => 'generated-excerpt-post',
            'title' => 'Generated Excerpt Post',
            'body' => '<p>First introduction line.</p><p>Second summary line.</p><p>Third line.</p>',
            'author_name' => 'iLamp Team',
            'is_published' => true,
        ]);

        $this->assertSame(
            'First introduction line. Second summary line.',
            $post->fresh()->excerpt,
        );

        $this->getJson('/api/blog/posts/generated-excerpt-post')
            ->assertOk()
            ->assertJsonPath('excerpt', 'First introduction line. Second summary line.');
    }

    public function test_blog_post_keeps_manual_excerpt_when_provided(): void
    {
        $post = BlogPost::query()->create([
            'slug' => 'manual-excerpt-post',
            'title' => 'Manual Excerpt Post',
            'excerpt' => 'Manual excerpt',
            'body' => '<p>First introduction line.</p><p>Second summary line.</p>',
            'author_name' => 'iLamp Team',
            'is_published' => true,
        ]);

        $this->assertSame('Manual excerpt', $post->fresh()->excerpt);

        $this->getJson('/api/blog/posts/manual-excerpt-post')
            ->assertOk()
            ->assertJsonPath('excerpt', 'Manual excerpt');
    }

    public function test_portfolio_projects_endpoint_sorts_latest_published_first(): void
    {
        PortfolioProject::query()->create([
            'slug' => 'older-project',
            'title' => 'Older Project',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        PortfolioProject::query()->create([
            'slug' => 'newer-project',
            'title' => 'Newer Project',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->getJson('/api/portfolio/projects')
            ->assertOk()
            ->assertJsonPath('0.slug', 'newer-project')
            ->assertJsonPath('1.slug', 'older-project');
    }

    public function test_contact_endpoint_stores_messages(): void
    {
        $this->seed();

        $payload = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'New Project',
            'message' => 'I need a website and consultation.',
        ];

        $this->postJson('/api/contact', $payload)
            ->assertCreated()
            ->assertJsonPath('message', 'Your message has been received.');

        $this->assertDatabaseHas((new ContactMessage())->getTable(), [
            'email' => 'jane@example.com',
            'subject' => 'New Project',
        ]);
    }

    public function test_consultation_availability_endpoint_returns_month_data(): void
    {
        $this->seed();

        $month = now()->addMonth()->format('Y-m');

        $this->getJson("/api/consultation/availability?month={$month}")
            ->assertOk()
            ->assertJsonPath('month', $month);
    }

    public function test_consultation_weekend_is_friday_and_saturday(): void
    {
        $this->seed();

        $service = app(ConsultationAvailabilityService::class);
        $baseDate = now()->addDay()->startOfDay();
        $sunday = $baseDate->copy()->next(Carbon::SUNDAY);
        $friday = $baseDate->copy()->next(Carbon::FRIDAY);
        $saturday = $baseDate->copy()->next(Carbon::SATURDAY);

        $this->assertNotEmpty($service->slotsForDate($sunday));
        $this->assertSame([], $service->slotsForDate($friday));
        $this->assertSame([], $service->slotsForDate($saturday));
    }
}
