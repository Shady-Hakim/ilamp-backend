<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $pageId = DB::table('pages')
            ->where('slug', 'about')
            ->value('id');

        if (! $pageId) {
            return;
        }

        $section = DB::table('page_sections')
            ->where('page_id', $pageId)
            ->where('key', 'faq')
            ->where('type', 'faq')
            ->first();

        if (! $section) {
            return;
        }

        $content = json_decode((string) $section->content, true);

        if (! is_array($content)) {
            return;
        }

        $items = $content['items'] ?? null;

        if (is_array($items) && count($items) > 0) {
            return;
        }

        $content['items'] = [
            [
                'q' => 'What is iLamp Agency and what services do you provide?',
                'a' => 'iLamp Agency is a full-service software development and digital marketing company specializing in custom web development, mobile app development, branding, SEO optimization, content marketing, web hosting, and business growth solutions. We help startups, SMEs, and enterprise clients build scalable digital products and increase online visibility through strategic marketing and technology.',
            ],
            [
                'q' => 'Is iLamp Agency a software development company or a digital marketing agency?',
                'a' => 'iLamp Agency is both a software development company and a digital marketing agency. We combine technical expertise with growth-driven marketing strategies to deliver complete digital transformation solutions. Our integrated approach ensures your website, app, branding, SEO, and advertising campaigns work together to maximize ROI.',
            ],
            [
                'q' => 'What industries does iLamp Agency work with?',
                'a' => 'We work with clients across various industries including real estate, e-commerce, healthcare, education, corporate services, and startups and tech companies. Our custom software solutions and digital marketing strategies are tailored to each business model and industry requirement.',
            ],
            [
                'q' => 'Why choose iLamp Agency over other web development agencies?',
                'a' => "Clients choose iLamp Agency because we focus on custom software solutions (not templates), SEO-first development strategy, performance-optimized websites, conversion-focused UI/UX design, long-term technical support and maintenance, and measurable digital marketing results. We don't just build websites - we build digital assets that generate leads and sales.",
            ],
            [
                'q' => 'Does iLamp Agency build SEO-friendly websites?',
                'a' => 'Yes. Every website and mobile application we build follows best SEO practices, including semantic HTML structure, fast loading speed, Core Web Vitals optimization, mobile-first responsive design, clean URL architecture, schema markup implementation, and on-page SEO optimization. This ensures your website ranks better on search engines like Google.',
            ],
            [
                'q' => 'Do you provide custom software development solutions?',
                'a' => 'Absolutely. We specialize in custom software development, including custom web applications, SaaS platforms, e-commerce systems, CRM & ERP systems, business automation tools, and API integrations. All solutions are scalable, secure, and built using modern technologies.',
            ],
            [
                'q' => 'Does iLamp Agency offer mobile app development?',
                'a' => 'Yes. We provide iOS and Android mobile app development services using modern frameworks and scalable architecture. Our mobile applications are designed for performance, security, and exceptional user experience.',
            ],
            [
                'q' => 'How does iLamp Agency help businesses grow online?',
                'a' => 'We help businesses grow by combining SEO optimization services, Search Engine Marketing (SEM), social media marketing, content marketing, branding strategy, and conversion rate optimization (CRO). Our goal is to increase website traffic, improve search rankings, and generate qualified leads.',
            ],
            [
                'q' => 'Do you offer website support and maintenance services?',
                'a' => 'Yes. We provide ongoing technical support and website maintenance to ensure security updates, performance monitoring, bug fixes, feature enhancements, hosting management, and backup solutions. We become your long-term digital partner.',
            ],
            [
                'q' => 'Can startups work with iLamp Agency?',
                'a' => 'Yes. We actively support startups with MVP development, product strategy consultation, branding & identity, SEO foundation setup, and growth marketing strategy. We help startups go from idea to scalable product.',
            ],
            [
                'q' => 'What makes iLamp Agency different from other digital marketing companies?',
                'a' => 'Our main differentiator is integration. Many agencies either focus on marketing or development. At iLamp Agency, we integrate technology, branding, SEO, marketing, and business strategy. This ensures consistent messaging, technical performance, and measurable results.',
            ],
            [
                'q' => 'Where is iLamp Agency located and do you work internationally?',
                'a' => 'iLamp Agency works with clients locally and internationally. Thanks to our remote-first approach, we serve businesses worldwide while maintaining strong communication and project management standards.',
            ],
        ];

        DB::table('page_sections')
            ->where('id', $section->id)
            ->update([
                'content' => json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No-op: this only restores missing FAQ items.
    }
};
