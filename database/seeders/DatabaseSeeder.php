<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\BlogCategory;
use App\Models\ConsultationAvailabilityRule;
use App\Models\ConsultationEmailSetting;
use App\Models\MailSetting;
use App\Models\Page;
use App\Models\PortfolioProject;
use App\Models\PortfolioCategory;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use App\Models\TimelineItem;
use App\Services\MediaLibraryService;
use App\Support\FrontendContentImporter;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Throwable;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $frontendContent = $this->loadFrontendContent();

        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@ilampagency.com')],
            [
                'name' => env('ADMIN_NAME', 'iLamp Admin'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'changeme123')),
                'is_admin' => true,
            ],
        );

        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'site_name' => 'iLamp Agency',
                'site_tagline' => 'AI development and digital growth',
                'footer_description' => 'Leading AI development company building custom AI solutions that drive business growth.',
                'contact_email' => 'info@ilampagency.com',
                'contact_phone' => '+1 (234) 567-890',
                'contact_address' => 'Global Remote Team',
                'whatsapp_url' => '+1234567890',
                'response_time_text' => 'We typically respond within 2-4 hours during business days.',
                'social_links' => [
                    ['icon' => 'linkedin', 'url' => 'https://linkedin.com'],
                    ['icon' => 'instagram', 'url' => 'https://instagram.com'],
                    ['icon' => 'facebook', 'url' => 'https://facebook.com'],
                ],
            ],
        );

        MailSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'mailer' => 'smtp',
                'from_name' => 'iLamp Agency',
                'from_email' => 'info@ilampagency.com',
                'notify_contact_to' => 'info@ilampagency.com',
                'notify_consultation_to' => 'info@ilampagency.com',
            ],
        );

        ConsultationEmailSetting::query()->updateOrCreate(
            ['id' => 1],
            array_merge(
                ['name' => 'Consultation Emails'],
                ConsultationEmailSetting::defaultBodies(),
            ),
        );

        $this->seedPages($frontendContent);
        $this->seedServices($frontendContent);
        $this->seedPortfolioCategories($frontendContent);
        $this->seedPortfolioProjects($frontendContent);
        $this->seedBlogCategories($frontendContent);
        $this->seedBlogPosts($frontendContent);
        $this->seedTestimonials($frontendContent);
        $this->seedTimeline($frontendContent);
        $this->seedAvailabilityRules();
        app(MediaLibraryService::class)->syncAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function loadFrontendContent(): ?array
    {
        try {
            return app(FrontendContentImporter::class)->load();
        } catch (Throwable) {
            return null;
        }
    }

    protected function seedPages(?array $frontendContent = null): void
    {
        $aboutFaqItems = collect(data_get($frontendContent, 'about.aboutFaqs', []))
            ->map(fn (array $faq): array => [
                'q' => $faq['q'],
                'a' => $faq['a'],
            ])
            ->values()
            ->all();

        $pages = [
            'home' => [
                'title' => 'Home',
                'sections' => [
                    [
                        'key' => 'hero',
                        'type' => 'hero',
                        'sort_order' => 10,
                        'content' => [
                            'badge' => 'iLamp Agency',
                            'title' => 'We Build',
                            'rotatingWords' => ['AI Systems', 'Web Platforms', 'Growth Engines', 'Smart Automation'],
                            'description' => 'Custom AI software, digital platforms, and growth systems built for ambitious businesses.',
                            'primaryButtonText' => 'Start a Project',
                            'primaryButtonUrl' => '/consultation',
                            'secondaryButtonText' => 'Contact Us',
                            'secondaryButtonUrl' => '/contact',
                        ],
                    ],
                    [
                        'key' => 'stats',
                        'type' => 'stats',
                        'sort_order' => 20,
                        'content' => [
                            'items' => [
                                ['end' => 100, 'suffix' => '+', 'label' => 'Projects Delivered'],
                                ['end' => 50, 'suffix' => '+', 'label' => 'Happy Clients'],
                                ['end' => 10, 'suffix' => '+', 'label' => 'Years Experience'],
                                ['end' => 99, 'suffix' => '%', 'label' => 'Client Satisfaction'],
                            ],
                        ],
                    ],
                    [
                        'key' => 'why_choose_us',
                        'type' => 'cards',
                        'sort_order' => 30,
                        'content' => [
                            'badge' => 'Why iLamp',
                            'title' => 'Built for Results,',
                            'highlight' => 'Designed to Scale',
                            'description' => "We don't just build products. We engineer AI-powered growth engines for ambitious businesses.",
                            'items' => [
                                [
                                    'title' => 'AI-First Approach',
                                    'desc' => 'Every solution leverages custom AI to optimize speed, scalability, and conversion.',
                                ],
                                [
                                    'title' => 'Full-Stack AI Expertise',
                                    'desc' => 'From chatbots to enterprise systems, our team covers development, integration, and automation.',
                                ],
                                [
                                    'title' => 'AI Growth Partners',
                                    'desc' => 'We align technology and business goals so automation drives measurable growth.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'faq',
                        'type' => 'faq',
                        'sort_order' => 35,
                        'content' => [
                            'badge' => 'FAQs',
                            'title' => 'Common Questions',
                            'highlight' => 'Answered',
                            'items' => [
                                [
                                    'q' => 'What does iLamp Agency build?',
                                    'a' => 'We build custom websites, AI software, mobile apps, automation systems, and growth-focused digital experiences for startups and established businesses.',
                                ],
                                [
                                    'q' => 'Do you handle both strategy and execution?',
                                    'a' => 'Yes. We cover discovery, UX, development, AI integration, launch, and ongoing support so projects move from idea to production with one team.',
                                ],
                                [
                                    'q' => 'Can you work with early-stage startups?',
                                    'a' => 'Yes. We help startups validate ideas, ship MVPs, establish a scalable technical foundation, and prepare for growth.',
                                ],
                                [
                                    'q' => 'What makes iLamp different from a typical agency?',
                                    'a' => 'We combine product thinking, engineering depth, and growth strategy instead of treating design, development, and marketing as isolated services.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'cta',
                        'type' => 'cta',
                        'sort_order' => 40,
                        'content' => [
                            'title' => 'Ready to',
                            'highlight' => 'Transform',
                            'afterHighlight' => ' Your Business with AI?',
                            'description' => "Let's discuss how our AI development company can help you automate, scale, and grow.",
                            'buttonText' => 'Book Consultation',
                            'buttonUrl' => '/consultation',
                        ],
                    ],
                ],
            ],
            'about' => [
                'title' => 'About',
                'sections' => [
                    [
                        'key' => 'hero',
                        'type' => 'hero',
                        'sort_order' => 10,
                        'content' => [
                            'badge' => 'About iLamp',
                            'eyebrow' => 'Lighting the Path to Success',
                            'title' => 'Building the Future with',
                            'highlight' => 'AI Solutions',
                            'description' => 'iLamp Agency is a leading AI development company and digital growth partner dedicated to helping businesses thrive.',
                        ],
                    ],
                    [
                        'key' => 'intro',
                        'type' => 'content',
                        'sort_order' => 20,
                        'content' => [
                            'title' => 'Your Partner in',
                            'highlight' => 'Business Growth',
                            'description' => '<p>Starting a new business demands vision, strategy, and the right partner. At iLamp Software &amp; Business Development, we are your guiding light, illuminating the path to measurable success through AI software development, AI automation services, scalable business growth strategies, and performance-driven digital marketing.</p>
<p>From AI for small business to enterprise AI solutions, we partner with companies at every stage of growth to deliver AI-driven insights, custom systems, and sustainable long-term growth.</p>',
                            'buttonText' => 'Check Our Services Out',
                            'buttonUrl' => '/services',
                        ],
                    ],
                    [
                        'key' => 'mission_vision',
                        'type' => 'cards',
                        'sort_order' => 30,
                        'content' => [
                            'items' => [
                                [
                                    'title' => 'Our',
                                    'highlight' => 'Mission',
                                    'tagline' => 'Empowering Growth, Transforming Visions into Reality',
                                    'description' => 'To empower businesses by providing AI automation services, custom AI solutions, and AI-powered digital marketing that drive growth, profitability, and sustainable success.',
                                ],
                                [
                                    'title' => 'Our',
                                    'highlight' => 'Vision',
                                    'tagline' => 'Guiding Businesses to Shine Brighter and Reach Further',
                                    'description' => 'To become a trusted leader as an AI development company, known for guiding businesses toward lasting success with enterprise AI solutions and standing out through software development and digital growth excellence.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'values',
                        'type' => 'cards',
                        'sort_order' => 40,
                        'content' => [
                            'badge' => 'Our Values',
                            'title' => 'Our Core',
                            'highlight' => 'Values',
                            'items' => [
                                [
                                    'title' => 'Innovation',
                                    'description' => 'We continuously seek out new ideas, pushing boundaries to keep our clients ahead of the curve.',
                                    'iconKey' => 'Lightbulb',
                                ],
                                [
                                    'title' => 'Customer Focus',
                                    'description' => "Our clients' needs and goals are our top priority. We are dedicated to helping them achieve sustainable success.",
                                    'iconKey' => 'Target',
                                ],
                                [
                                    'title' => 'Excellence',
                                    'description' => 'We strive to deliver outstanding quality in every project, ensuring solutions that add real value to our clients’ businesses.',
                                    'iconKey' => 'Award',
                                ],
                                [
                                    'title' => 'Integrity',
                                    'description' => 'We hold ourselves accountable to the highest standards, maintaining transparency and honesty in all interactions.',
                                    'iconKey' => 'Shield',
                                ],
                                [
                                    'title' => 'Collaboration',
                                    'description' => 'Teamwork is at the heart of our process. We believe in working closely with clients to achieve the best outcomes.',
                                    'iconKey' => 'Users',
                                ],
                                [
                                    'title' => 'Adaptability',
                                    'description' => 'We stay agile and responsive, continuously evolving our strategies to meet the changing demands of the business landscape.',
                                    'iconKey' => 'RefreshCw',
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'impact',
                        'type' => 'stats',
                        'sort_order' => 50,
                        'content' => [
                            'badge' => 'Our Impact',
                            'title' => 'Illuminating',
                            'highlight' => 'Success',
                            'description' => 'We create measurable outcomes for clients by combining strategy, innovation, and execution into systems that drive sustainable business growth.',
                            'items' => [
                                ['end' => 100, 'suffix' => '+', 'label' => 'Projects Delivered'],
                                ['end' => 50, 'suffix' => '+', 'label' => 'Happy Clients'],
                                ['end' => 10, 'suffix' => '+', 'label' => 'Years Experience'],
                                ['end' => 99, 'suffix' => '%', 'label' => 'Client Satisfaction'],
                            ],
                        ],
                    ],
                    [
                        'key' => 'faq',
                        'type' => 'faq',
                        'sort_order' => 60,
                        'content' => [
                            'badge' => 'FAQs',
                            'title' => 'Frequently Asked',
                            'highlight' => 'Questions',
                            'items' => $aboutFaqItems !== [] ? $aboutFaqItems : [
                                [
                                    'q' => 'What is iLamp Agency and what services do you provide?',
                                    'a' => 'iLamp Agency is a software development and digital marketing company specializing in custom web, mobile, branding, SEO, content, hosting, and business growth solutions.',
                                ],
                                [
                                    'q' => 'Can startups work with iLamp Agency?',
                                    'a' => 'Yes. We support startups with MVP development, product strategy, branding, SEO foundations, and growth planning.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'cta',
                        'type' => 'cta',
                        'sort_order' => 70,
                        'content' => [
                            'title' => 'Know the Team,',
                            'highlight' => 'Start the Project',
                            'description' => "Let's talk about what we can build for your business.",
                            'buttonText' => 'Free Consultation',
                            'buttonUrl' => '/consultation',
                        ],
                    ],
                ],
            ],
            'services' => [
                'title' => 'Services',
                'sections' => [
                    [
                        'key' => 'hero',
                        'type' => 'hero',
                        'sort_order' => 10,
                        'content' => [
                            'badge' => 'iLamp Services',
                            'title' => 'AI-Powered',
                            'highlight' => 'Digital Solutions',
                            'description' => 'From AI chatbot development to business automation with AI, we provide custom digital solutions built to scale.',
                        ],
                    ],
                    [
                        'key' => 'intro',
                        'type' => 'content',
                        'sort_order' => 20,
                        'content' => [
                            'description' => 'You have seconds to capture attention in a crowded digital market. We combine strategy, design, development, and AI-powered execution to deliver systems that stand out and scale.',
                        ],
                    ],
                    [
                        'key' => 'addons',
                        'type' => 'cards',
                        'sort_order' => 30,
                        'content' => [
                            'badge' => 'Service Packages',
                            'title' => 'Add-Ons Available for',
                            'highlight' => 'Any Package',
                            'description' => 'Choose the extra services that fit your project best.',
                            'items' => [
                                ['title' => 'Hosting & Domain', 'description' => 'Reliable hosting and domain registration to get your business online fast.', 'iconKey' => 'Globe'],
                                ['title' => 'Email Setup', 'description' => 'Professional email accounts configured to match your brand identity.', 'iconKey' => 'Mail'],
                                ['title' => 'App Store Publishing', 'description' => 'End-to-end app submission and approval for iOS and Android stores.', 'iconKey' => 'Rocket'],
                                ['title' => 'Video Production', 'description' => 'Engaging promotional and explainer videos that captivate your audience.', 'iconKey' => 'Video'],
                                ['title' => 'E-commerce Integration', 'description' => 'Seamless online store setup with secure payment and inventory management.', 'iconKey' => 'ShoppingCart'],
                                ['title' => 'Multilingual Support', 'description' => 'Reach global audiences with professional translation and localization.', 'iconKey' => 'Languages'],
                            ],
                        ],
                    ],
                    [
                        'key' => 'faq',
                        'type' => 'faq',
                        'sort_order' => 40,
                        'content' => [
                            'badge' => 'FAQs',
                            'title' => 'Frequently Asked',
                            'highlight' => 'Questions',
                            'items' => [
                                [
                                    'q' => 'What AI-powered digital solutions does iLamp provide?',
                                    'a' => 'We provide end-to-end AI-powered digital solutions, including AI chatbot development, AI automation services, custom AI web applications, enterprise AI systems, AI marketing optimization, and AI-driven SEO strategies.',
                                ],
                                [
                                    'q' => 'What is AI chatbot development and how can it help my business?',
                                    'a' => 'AI chatbot development creates intelligent chat systems that automate support, qualify leads, answer FAQs, and improve response times across websites, apps, and messaging channels.',
                                ],
                                [
                                    'q' => 'Do you build custom AI software solutions?',
                                    'a' => 'Yes. We build custom AI software tailored to your business goals, including machine learning integrations, predictive analytics systems, automation workflows, and enterprise AI implementations.',
                                ],
                                [
                                    'q' => 'What is AI business automation?',
                                    'a' => 'AI business automation uses artificial intelligence to streamline repetitive tasks and workflows such as support, CRM updates, reporting, campaign optimization, and sales analysis.',
                                ],
                                [
                                    'q' => 'Can AI improve digital marketing performance?',
                                    'a' => 'Yes. AI can optimize targeting, predict customer behavior, automate campaign management, improve conversion rates, and uncover new growth opportunities faster than manual processes alone.',
                                ],
                                [
                                    'q' => 'Do you provide AI-powered mobile app development?',
                                    'a' => 'Yes. We develop native and cross-platform mobile applications with AI features such as chatbots, smart recommendations, predictive analytics, and NLP-driven assistants.',
                                ],
                                [
                                    'q' => 'How secure are AI-powered systems?',
                                    'a' => 'Security is built into every project through secure APIs, access controls, encryption, monitoring, and infrastructure best practices that protect both user data and business operations.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'cta',
                        'type' => 'cta',
                        'sort_order' => 50,
                        'content' => [
                            'title' => 'Need a Custom',
                            'highlight' => 'Solution',
                            'description' => 'Start with a free consultation and let us shape the right service package for your goals.',
                            'buttonText' => 'Book Consultation',
                            'buttonUrl' => '/consultation',
                        ],
                    ],
                ],
            ],
            'portfolio' => [
                'title' => 'Portfolio',
                'sections' => [
                    [
                        'key' => 'hero',
                        'type' => 'hero',
                        'sort_order' => 10,
                        'content' => [
                            'badge' => 'Our Work',
                            'title' => 'Explore Our',
                            'highlight' => 'Portfolio',
                            'description' => 'Case studies across web development, mobile apps, branding, and digital marketing.',
                        ],
                    ],
                    [
                        'key' => 'faq',
                        'type' => 'faq',
                        'sort_order' => 20,
                        'content' => [
                            'badge' => 'FAQs',
                            'title' => 'Portfolio',
                            'highlight' => 'Questions',
                            'items' => [
                                [
                                    'q' => 'What kinds of projects do you include in your portfolio?',
                                    'a' => 'Our portfolio spans websites, SaaS platforms, mobile apps, branding systems, AI-powered products, and digital campaigns across multiple industries.',
                                ],
                                [
                                    'q' => 'Can you build something similar to a featured case study?',
                                    'a' => 'Yes. Case studies show representative outcomes and workflows, but every new engagement is scoped around the client’s exact goals, constraints, and market.',
                                ],
                                [
                                    'q' => 'Do you only take on large enterprise work?',
                                    'a' => 'No. We work with startups, SMEs, and enterprise teams. Project scope, not company size, determines the right delivery model.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'cta',
                        'type' => 'cta',
                        'sort_order' => 30,
                        'content' => [
                            'title' => 'Have a Project in',
                            'highlight' => 'Mind',
                            'afterHighlight' => '?',
                            'description' => "Let's collaborate and bring your vision to life with cutting-edge technology and creative excellence.",
                            'buttonText' => 'Free Consultation',
                            'buttonUrl' => '/consultation',
                        ],
                    ],
                ],
            ],
            'blog' => [
                'title' => 'Blog',
                'sections' => [
                    [
                        'key' => 'hero',
                        'type' => 'hero',
                        'sort_order' => 10,
                        'content' => [
                            'badge' => 'Insights & Articles',
                            'title' => 'Latest',
                            'highlight' => 'Insights',
                            'description' => 'Thoughts on AI, development, marketing, SaaS, and business growth.',
                        ],
                    ],
                    [
                        'key' => 'faq',
                        'type' => 'faq',
                        'sort_order' => 20,
                        'content' => [
                            'badge' => 'FAQs',
                            'title' => 'Blog',
                            'highlight' => 'Questions',
                            'items' => [
                                [
                                    'q' => 'What topics does the iLamp blog cover?',
                                    'a' => 'We publish insights on AI, web development, SaaS growth, SEO, digital marketing, automation, and business strategy.',
                                ],
                                [
                                    'q' => 'Are these articles written for founders or technical teams?',
                                    'a' => 'Both. Some articles focus on strategic decision-making while others go deeper into execution, architecture, and optimization.',
                                ],
                                [
                                    'q' => 'Can I contact iLamp about a topic covered in an article?',
                                    'a' => 'Yes. If a post aligns with your project, you can reach out through the contact page or book a consultation to discuss implementation.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'newsletter',
                        'type' => 'cta',
                        'sort_order' => 30,
                        'content' => [
                            'title' => 'Stay Ahead with',
                            'highlight' => 'Fresh Insights',
                            'description' => 'Subscribe to get practical updates on AI, digital products, and growth.',
                            'buttonText' => 'Subscribe',
                            'buttonUrl' => '#newsletter',
                        ],
                    ],
                ],
            ],
            'contact' => [
                'title' => 'Contact',
                'sections' => [
                    [
                        'key' => 'hero',
                        'type' => 'hero',
                        'sort_order' => 10,
                        'content' => [
                            'badge' => 'Contact Us',
                            'title' => "Let's Start Your",
                            'highlight' => 'Project',
                            'description' => 'Ready to transform your business? Get in touch for a free consultation.',
                        ],
                    ],
                    [
                        'key' => 'contact_cards',
                        'type' => 'cards',
                        'sort_order' => 20,
                        'content' => [
                            'items' => [
                                ['title' => 'Email Us', 'value' => 'info@ilampagency.com', 'href' => 'mailto:info@ilampagency.com', 'iconKey' => 'Mail'],
                                ['title' => 'Call Us', 'value' => '+1 (234) 567-890', 'href' => 'tel:+1234567890', 'iconKey' => 'Phone'],
                                ['title' => 'Location', 'value' => 'Global Remote Team', 'href' => '#', 'iconKey' => 'MapPin'],
                            ],
                        ],
                    ],
                    [
                        'key' => 'faq',
                        'type' => 'faq',
                        'sort_order' => 25,
                        'content' => [
                            'badge' => 'FAQs',
                            'title' => 'Contact',
                            'highlight' => 'Questions',
                            'items' => [
                                [
                                    'q' => 'What should I include in my first message?',
                                    'a' => 'Share your business goals, the product or campaign you need help with, your timeline, and any technical context that would help us prepare.',
                                ],
                                [
                                    'q' => 'How fast do you usually respond?',
                                    'a' => 'We typically reply within a few business hours. For urgent cases, WhatsApp is the fastest direct channel.',
                                ],
                                [
                                    'q' => 'Can I contact you even if my project is still at the idea stage?',
                                    'a' => 'Yes. Early conversations are useful for shaping scope, clarifying requirements, and identifying the right starting point.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'key' => 'quick_response',
                        'type' => 'content',
                        'sort_order' => 30,
                        'content' => [
                            'title' => 'Quick Response',
                            'description' => 'We typically respond within 2-4 hours during business days. For urgent inquiries, reach us via WhatsApp.',
                        ],
                    ],
                ],
            ],
            'consultation' => [
                'title' => 'Consultation',
                'sections' => [
                    [
                        'key' => 'hero',
                        'type' => 'hero',
                        'sort_order' => 10,
                        'content' => [
                            'badge' => 'Free Consultation',
                            'title' => 'Book Your Free',
                            'highlight' => 'Consultation',
                            'description' => 'Speak with our experts about AI solutions, software development, and digital growth strategies.',
                        ],
                    ],
                    [
                        'key' => 'trust_badges',
                        'type' => 'cards',
                        'sort_order' => 20,
                        'content' => [
                            'items' => [
                                ['text' => 'Personalized strategy session', 'iconKey' => 'Sparkles'],
                                ['text' => 'AI & technology consultation', 'iconKey' => 'Brain'],
                                ['text' => 'Business growth insights', 'iconKey' => 'TrendingUp'],
                            ],
                        ],
                    ],
                    [
                        'key' => 'calendar_intro',
                        'type' => 'content',
                        'sort_order' => 30,
                        'content' => [
                            'title' => 'Choose a Date and Time',
                            'description' => 'Select an available day on the calendar and reserve a strategy session that fits your schedule.',
                        ],
                    ],
                    [
                        'key' => 'faq',
                        'type' => 'faq',
                        'sort_order' => 40,
                        'content' => [
                            'badge' => 'FAQs',
                            'title' => 'Consultation',
                            'highlight' => 'Questions',
                            'items' => [
                                [
                                    'q' => 'Is the consultation free?',
                                    'a' => 'Yes. The initial consultation is free and focused on understanding your goals, requirements, and the best next steps.',
                                ],
                                [
                                    'q' => 'How long is each consultation slot?',
                                    'a' => 'Current default slots are one hour. Availability is managed from the admin dashboard and can be adjusted over time.',
                                ],
                                [
                                    'q' => 'What happens after I reserve a slot?',
                                    'a' => 'Your reservation is stored in the dashboard, the selected slot is blocked from further bookings, and the team can review, confirm, and follow up from the admin panel.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($pages as $slug => $pageData) {
            $page = Page::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $pageData['title'],
                    'meta_title' => $pageData['title'].' | iLamp Agency',
                    'meta_description' => $pageData['title'].' page content managed from the dashboard.',
                    'is_published' => true,
                ],
            );

            foreach ($pageData['sections'] as $section) {
                $page->sections()->updateOrCreate(
                    ['key' => $section['key']],
                    [
                        'type' => $section['type'],
                        'content' => $section['content'],
                        'sort_order' => $section['sort_order'],
                        'is_enabled' => true,
                    ],
                );
            }
        }
    }

    protected function seedServices(?array $frontendContent = null): void
    {
        $importedServices = collect(data_get($frontendContent, 'services.services', []));
        $importedDetails = collect(data_get($frontendContent, 'serviceDetails.serviceDetails', []));

        if ($importedServices->isNotEmpty()) {
            foreach ($importedServices as $serviceData) {
                $detail = $importedDetails->get($serviceData['slug'], []);

                Service::query()->updateOrCreate(
                    ['slug' => $serviceData['slug']],
                    [
                        'title' => $serviceData['title'],
                        'short_description' => $serviceData['desc'],
                        'icon_key' => $serviceData['icon'],
                        'features' => $serviceData['features'] ?? [],
                        'headline' => $detail['headline'] ?? null,
                        'subheadline' => $detail['subheadline'] ?? null,
                        'description' => $detail['description'] ?? null,
                        'benefits' => $detail['benefits'] ?? [],
                        'process_steps' => $detail['process'] ?? [],
                        'faq_items' => $detail['faqs'] ?? [],
                        'seo_title' => $serviceData['title'].' | iLamp Agency',
                        'seo_description' => $serviceData['desc'],
                        'is_published' => true,
                    ],
                );
            }

            return;
        }

        $services = [
            [
                'slug' => 'web-development',
                'title' => 'Web & AI Software Development',
                'short_description' => 'Custom websites and AI-powered web applications built with modern frameworks.',
                'icon_key' => 'Code',
                'features' => ['Custom Websites', 'AI Web Apps', 'E-Commerce', 'Progressive Web Apps', 'AI Integration', 'Machine Learning'],
            ],
            [
                'slug' => 'mobile-development',
                'title' => 'Mobile App & AI Chatbot Development',
                'short_description' => 'Native and cross-platform apps alongside intelligent AI chatbots.',
                'icon_key' => 'Smartphone',
                'features' => ['iOS & Android Apps', 'Cross-Platform Apps', 'AI Chatbots', 'Virtual Assistants', 'NLP Integration', 'Push Notifications'],
            ],
            [
                'slug' => 'digital-marketing',
                'title' => 'Digital Marketing & AI Campaigns',
                'short_description' => 'Full-spectrum digital marketing powered by AI.',
                'icon_key' => 'BarChart3',
                'features' => ['Social Media Marketing', 'PPC Campaigns', 'AI Ad Optimization', 'Predictive Analytics', 'Email Marketing', 'Marketing Automation'],
            ],
            [
                'slug' => 'seo',
                'title' => 'SEO & AI-Driven Optimization',
                'short_description' => 'Comprehensive SEO strategies enhanced by AI to drive qualified organic traffic.',
                'icon_key' => 'Search',
                'features' => ['Technical SEO', 'On-Page SEO', 'AI SEO Analysis', 'AI Link Building', 'Local SEO', 'Keyword Research'],
            ],
            [
                'slug' => 'branding',
                'title' => 'Branding & AI-Enhanced UI/UX',
                'short_description' => 'Create a memorable brand identity with AI-driven design insights.',
                'icon_key' => 'Palette',
                'features' => ['Logo & Brand Identity', 'UI/UX Design', 'Brand Guidelines', 'Design Systems', 'Wireframing', 'Prototyping'],
            ],
            [
                'slug' => 'content-writing',
                'title' => 'Content Writing & AI Content Marketing',
                'short_description' => 'Professional content creation enhanced by AI that educates and converts.',
                'icon_key' => 'PenTool',
                'features' => ['Blog & Article Writing', 'AI Content Generation', 'Copywriting', 'Content Strategy', 'Social Media Content', 'SEO Content'],
            ],
            [
                'slug' => 'web-hosting',
                'title' => 'Web Hosting & AI Automation',
                'short_description' => 'Reliable web hosting with AI-powered automation and monitoring.',
                'icon_key' => 'Server',
                'features' => ['Cloud Hosting', 'Managed Servers', 'Process Automation', 'AI Workflows', 'SSL & Security', 'Performance Monitoring'],
            ],
            [
                'slug' => 'support',
                'title' => 'Technical Support & Enterprise AI Solutions',
                'short_description' => 'End-to-end technical support and enterprise AI solutions.',
                'icon_key' => 'Headphones',
                'features' => ['24/7 Support', 'AI Monitoring', 'Model Tuning', 'Performance Optimization', 'System Maintenance', 'Dedicated Account Manager'],
            ],
        ];

        foreach ($services as $service) {
            Service::query()->updateOrCreate(['slug' => $service['slug']], $service);
        }
    }

    protected function seedPortfolioCategories(?array $frontendContent = null): void
    {
        $importedCategories = collect(data_get($frontendContent, 'portfolio.portfolioCategories', []));

        if ($importedCategories->isNotEmpty()) {
            foreach ($importedCategories as $category) {
                PortfolioCategory::query()->updateOrCreate(
                    ['slug' => $category['slug']],
                    [
                        'id' => $category['id'],
                        'name' => $category['name'],
                        'description' => $category['description'],
                        'icon_key' => $category['icon'],
                        'is_published' => true,
                    ],
                );
            }

            return;
        }

        $categories = [
            ['id' => 1, 'slug' => 'web-development', 'name' => 'Web Development', 'description' => 'Custom websites, web applications, and platforms.', 'icon_key' => 'Globe'],
            ['id' => 2, 'slug' => 'mobile-apps', 'name' => 'Mobile Apps', 'description' => 'Native and cross-platform mobile applications.', 'icon_key' => 'Smartphone'],
            ['id' => 3, 'slug' => 'branding', 'name' => 'Branding', 'description' => 'Strategic brand identities and visual systems.', 'icon_key' => 'Palette'],
            ['id' => 4, 'slug' => 'digital-marketing', 'name' => 'Digital Marketing', 'description' => 'Multi-channel campaigns and growth marketing.', 'icon_key' => 'Megaphone'],
        ];

        foreach ($categories as $category) {
            PortfolioCategory::query()->updateOrCreate(['slug' => $category['slug']], $category);
        }
    }

    protected function seedPortfolioProjects(?array $frontendContent = null): void
    {
        $importedProjects = collect(data_get($frontendContent, 'portfolio.portfolioProjects', []));

        if ($importedProjects->isEmpty()) {
            return;
        }

        foreach ($importedProjects as $projectData) {
            $project = PortfolioProject::query()->updateOrCreate(
                ['slug' => $projectData['slug']],
                [
                    'title' => $projectData['title'],
                    'short_description' => $projectData['desc'],
                    'brief' => $projectData['brief'],
                    'tech_stack' => $projectData['tech'] ?? [],
                    'image_url' => $projectData['image'] ?? null,
                    'client' => $projectData['client'] ?? null,
                    'client_brief' => $projectData['clientBrief'] ?? null,
                    'client_logo_url' => $projectData['clientLogo'] ?? null,
                    'year' => $projectData['year'] ?? null,
                    'challenge' => $projectData['challenge'] ?? null,
                    'solution' => $projectData['solution'] ?? null,
                    'results' => $projectData['results'] ?? [],
                    'gallery' => $projectData['gallery'] ?? [],
                    'is_featured' => (bool) ($projectData['featured'] ?? false),
                    'is_published' => true,
                ],
            );

            $project->categories()->sync($projectData['categories'] ?? []);
        }
    }

    protected function seedBlogCategories(?array $frontendContent = null): void
    {
        $importedCategories = collect(data_get($frontendContent, 'blog.blogCategoryMeta', []));

        if ($importedCategories->isNotEmpty()) {
            foreach ($importedCategories as $category) {
                BlogCategory::query()->updateOrCreate(
                    ['slug' => $category['slug']],
                    [
                        'id' => $category['id'],
                        'name' => $category['name'],
                        'description' => $category['description'],
                        'is_published' => true,
                    ],
                );
            }

            return;
        }

        $categories = [
            ['id' => 1, 'name' => 'AI', 'slug' => 'ai', 'description' => 'Insights and technologies shaping the future of AI.'],
            ['id' => 2, 'name' => 'Web Development', 'slug' => 'web-development', 'description' => 'Frameworks, performance optimization, and modern web best practices.'],
            ['id' => 3, 'name' => 'Digital Marketing', 'slug' => 'digital-marketing', 'description' => 'Campaign strategy, social media, and growth tactics.'],
            ['id' => 4, 'name' => 'SEO', 'slug' => 'seo', 'description' => 'Search optimization techniques for better organic visibility.'],
            ['id' => 5, 'name' => 'SaaS', 'slug' => 'saas', 'description' => 'Scaling strategies for software-as-a-service businesses.'],
            ['id' => 6, 'name' => 'Technology', 'slug' => 'technology', 'description' => 'Emerging technology trends and development insights.'],
            ['id' => 7, 'name' => 'Business Growth', 'slug' => 'business-growth', 'description' => 'Automation and strategies to accelerate business growth.'],
        ];

        foreach ($categories as $category) {
            BlogCategory::query()->updateOrCreate(['slug' => $category['slug']], $category);
        }
    }

    protected function seedBlogPosts(?array $frontendContent = null): void
    {
        $importedPosts = collect(data_get($frontendContent, 'blog.blogPosts', []));

        if ($importedPosts->isEmpty()) {
            return;
        }

        foreach ($importedPosts as $postData) {
            $post = BlogPost::query()->updateOrCreate(
                ['slug' => $postData['slug']],
                [
                    'title' => $postData['title'],
                    'excerpt' => $postData['excerpt'],
                    'body' => $postData['description'],
                    'author_name' => $postData['author'] ?? 'iLamp Team',
                    'image_url' => $postData['image'] ?? null,
                    'published_at' => filled($postData['date'] ?? null) ? Carbon::parse($postData['date']) : now(),
                    'is_featured' => (bool) ($postData['featured'] ?? false),
                    'is_published' => true,
                    'seo_title' => $postData['title'].' | iLamp Agency',
                    'seo_description' => $postData['excerpt'],
                ],
            );

            $post->categories()->sync($postData['categories'] ?? []);
        }
    }

    protected function seedTestimonials(?array $frontendContent = null): void
    {
        $importedTestimonials = collect(data_get($frontendContent, 'testimonials.testimonials', []));

        if ($importedTestimonials->isNotEmpty()) {
            foreach ($importedTestimonials->values() as $index => $testimonial) {
                Testimonial::query()->updateOrCreate(
                    ['name' => $testimonial['name']],
                    [
                        'role' => $testimonial['role'],
                        'quote' => $testimonial['quote'],
                        'sort_order' => ($index + 1) * 10,
                        'is_published' => true,
                    ],
                );
            }

            return;
        }

        $testimonials = [
            ['name' => 'Sarah Chen', 'role' => 'CEO, TechVault', 'quote' => 'iLamp transformed our digital presence completely. Our conversions increased by 340% in just 3 months.', 'sort_order' => 10],
            ['name' => 'Michael Torres', 'role' => 'Founder, GrowthPulse', 'quote' => 'The team delivered beyond our expectations with a comprehensive strategy that boosted our organic traffic by 500%.', 'sort_order' => 20],
            ['name' => 'Emma Rodrigues', 'role' => 'CMO, NexaFlow', 'quote' => 'Working with iLamp felt like having a world-class tech team in-house. Highly recommended.', 'sort_order' => 30],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::query()->updateOrCreate(
                ['name' => $testimonial['name']],
                $testimonial,
            );
        }
    }

    protected function seedTimeline(?array $frontendContent = null): void
    {
        $importedItems = collect(data_get($frontendContent, 'about.aboutTimeline', []));

        if ($importedItems->isNotEmpty()) {
            foreach ($importedItems->values() as $index => $item) {
                TimelineItem::query()->updateOrCreate(
                    ['year' => $item['year'], 'title' => $item['title']],
                    [
                        'description' => $item['desc'],
                        'sort_order' => ($index + 1) * 10,
                        'is_published' => true,
                    ],
                );
            }

            return;
        }

        $items = [
            ['year' => '2016', 'title' => 'Founded', 'description' => 'Started as a freelance web development studio.', 'sort_order' => 10],
            ['year' => '2018', 'title' => 'Agency Launch', 'description' => 'Expanded into a full-service digital agency.', 'sort_order' => 20],
            ['year' => '2020', 'title' => 'Global Reach', 'description' => 'Served clients across 5+ countries.', 'sort_order' => 30],
            ['year' => '2022', 'title' => '100+ Projects', 'description' => 'Milestone of 100 successfully delivered projects.', 'sort_order' => 40],
            ['year' => '2024', 'title' => 'AI Integration', 'description' => 'Pioneered AI-driven solutions for business growth.', 'sort_order' => 50],
        ];

        foreach ($items as $item) {
            TimelineItem::query()->updateOrCreate(
                ['year' => $item['year'], 'title' => $item['title']],
                $item,
            );
        }
    }

    protected function seedAvailabilityRules(): void
    {
        $rules = [];

        foreach ([0, 1, 2, 3, 4] as $weekday) {
            $rules[] = ['weekday' => $weekday, 'start_time' => '10:00:00', 'end_time' => '12:00:00', 'slot_duration_minutes' => 60, 'buffer_minutes' => 0];
            $rules[] = ['weekday' => $weekday, 'start_time' => '13:00:00', 'end_time' => '17:00:00', 'slot_duration_minutes' => 60, 'buffer_minutes' => 0];
        }

        foreach ($rules as $rule) {
            ConsultationAvailabilityRule::query()->updateOrCreate(
                [
                    'weekday' => $rule['weekday'],
                    'start_time' => $rule['start_time'],
                    'end_time' => $rule['end_time'],
                ],
                $rule,
            );
        }
    }
}
