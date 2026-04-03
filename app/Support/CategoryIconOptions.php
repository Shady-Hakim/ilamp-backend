<?php

namespace App\Support;

use Illuminate\Support\Str;

class CategoryIconOptions
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'BrainCircuit' => 'Brain Circuit',
            'Code' => 'Code',
            'Globe' => 'Globe',
            'Smartphone' => 'Smartphone',
            'Palette' => 'Palette',
            'Megaphone' => 'Megaphone',
            'Layout' => 'Layout',
            'ShoppingCart' => 'Shopping Cart',
            'Search' => 'Search',
            'Cloud' => 'Cloud',
            'Landmark' => 'Landmark',
            'Share2' => 'Share',
            'BarChart3' => 'Bar Chart',
            'Dumbbell' => 'Dumbbell',
            'Cpu' => 'CPU',
            'BookOpen' => 'Book',
            'Rocket' => 'Rocket',
            'Lightbulb' => 'Lightbulb',
            'Bot' => 'AI Bot',
            'Layers' => 'Layers',
            'Briefcase' => 'Briefcase',
            'Building2' => 'Building',
            'Handshake' => 'Handshake',
            'TrendingUp' => 'Trending Up',
            'Target' => 'Target',
            'Compass' => 'Compass',
            'ChartLine' => 'Chart Line',
            'ChartPie' => 'Chart Pie',
            'Gem' => 'Gem',
            'ShieldCheck' => 'Shield Check',
            'Lock' => 'Lock',
            'Zap' => 'Zap',
            'MonitorSmartphone' => 'Responsive',
            'MousePointerClick' => 'CRO',
            'PenTool' => 'Design',
            'FileText' => 'Content',
            'Mail' => 'Email',
            'MessageCircle' => 'Messaging',
            'Users' => 'Users',
            'UserRound' => 'User',
            'Store' => 'Store',
            'Package' => 'Package',
            'Settings' => 'Settings',
            'Wrench' => 'Tools',
            'Sparkles' => 'Sparkles',
            'Flame' => 'Growth',
            'CircleDollarSign' => 'Revenue',
            'BadgeCheck' => 'Quality',
            'Headphones' => 'Support',
            'CalendarClock' => 'Scheduling',
            'GraduationCap' => 'Education',
            'Factory' => 'Industry',
            'Hotel' => 'Hospitality',
            'Stethoscope' => 'Healthcare',
            'Truck' => 'Logistics',
            'Gavel' => 'Legal',
            'Home' => 'Real Estate',
            'Banknote' => 'Finance',
            'Camera' => 'Media',
            'PlayCircle' => 'Video',
            'Podcast' => 'Podcast',
            'ScanSearch' => 'Audit',
            'Fingerprint' => 'Identity',
            'Network' => 'Networking',
            'Database' => 'Database',
            'Server' => 'Server',
            'Workflow' => 'Automation',
            'Bell' => 'Alerts',
            'Shield' => 'Security',
            'ShieldAlert' => 'Protection',
            'Award' => 'Awards',
            'Crown' => 'Premium',
            'MapPinned' => 'Location',
            'Map' => 'Map',
            'Navigation' => 'Navigation',
            'Link' => 'Link Building',
            'GitBranch' => 'Versioning',
            'Code2' => 'Engineering',
            'Terminal' => 'DevOps',
            'Laptop' => 'Desktop',
            'TabletSmartphone' => 'Mobile',
            'AppWindow' => 'Web App',
            'Image' => 'Image',
            'Clapperboard' => 'Production',
            'Presentation' => 'Presentation',
            'Kanban' => 'Project Management',
            'ClipboardCheck' => 'Checklist',
            'Timer' => 'Speed',
            'Clock3' => 'Time',
            'Activity' => 'Performance',
            'Gauge' => 'Optimization',
            'Goal' => 'Goals',
            'PiggyBank' => 'Savings',
            'Wallet' => 'Payments',
            'CreditCard' => 'Billing',
            'ReceiptText' => 'Invoices',
            'UserCheck' => 'Verified User',
            'UserCog' => 'Admin',
            'UsersRound' => 'Team',
            'UserPlus' => 'Acquisition',
            'Building' => 'Enterprise',
            'Building2' => 'Corporate',
            'ClipboardList' => 'Operations',
            'SlidersHorizontal' => 'Controls',
            'Puzzle' => 'Integrations',
            'Cable' => 'Connectivity',
            'Wifi' => 'Wireless',
            'QrCode' => 'QR',
            'ScanLine' => 'Scanner',
            'HeartPulse' => 'Wellness',
            'Leaf' => 'Sustainability',
            'Bug' => 'Debugging',
            'FileCode' => 'Code File',
            'GalleryVerticalEnd' => 'Showcase',
            'CircleHelp' => 'Help',
            'BookMarked' => 'Resources',
            'School' => 'Training',
            'Mic' => 'Audio',
            'Radar' => 'Insights',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function withPreview(): array
    {
        return collect(static::all())
            ->mapWithKeys(function (string $label, string $iconKey): array {
                return [
                    $iconKey => static::renderIconHtml($iconKey, $label, isSelected: false),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function searchWithPreview(?string $search): array
    {
        $search = Str::lower(trim((string) $search));

        return collect(static::all())
            ->filter(function (string $label, string $iconKey) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                return Str::contains(Str::lower($label), $search)
                    || Str::contains(Str::lower($iconKey), $search);
            })
            ->keys()
            ->mapWithKeys(function (string $iconKey): array {
                return [$iconKey => static::withPreview()[$iconKey]];
            })
            ->all();
    }

    public static function previewLabel(?string $iconKey): ?string
    {
        if (! $iconKey || ! array_key_exists($iconKey, static::all())) {
            return null;
        }

        $label = static::all()[$iconKey];

        return static::renderIconHtml($iconKey, $label, isSelected: true);
    }

    protected static function renderIconHtml(string $iconKey, string $label, bool $isSelected): string
    {
        $iconSlug = static::toLucideSlug($iconKey);
        $iconUrl = "https://unpkg.com/lucide-static@latest/icons/{$iconSlug}.svg";
        $classes = $isSelected ? 'ilamp-icon-option ilamp-icon-option--selected' : 'ilamp-icon-option';

        return '<span class="' . $classes . '" aria-label="' . e($label) . '" title="' . e($label) . '">'
            . '<img src="' . e($iconUrl) . '" alt="' . e($label) . '" class="ilamp-icon-option__img" loading="lazy" />'
            . '<span class="ilamp-icon-option__text">' . e($label) . '</span>'
            . '</span>';
    }

    protected static function toLucideSlug(string $iconKey): string
    {
        // Lucide uses hyphens before numeric suffixes (e.g. "share-2", "bar-chart-3").
        return (string) Str::of(Str::kebab($iconKey))
            ->replaceMatches('/(?<=\D)(\d+)/', '-$1');
    }
}
