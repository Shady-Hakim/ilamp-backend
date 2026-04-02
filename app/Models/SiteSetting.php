<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public static function socialIconOptions(): array
    {
        return [
            'linkedin' => 'LinkedIn',
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'behance' => 'Behance',
            'x' => 'X',
            'youtube' => 'YouTube',
            'tiktok' => 'TikTok',
            'github' => 'GitHub',
            'whatsapp' => 'WhatsApp',
            'globe' => 'Website',
        ];
    }

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
        ];
    }

    public static function normalizeWhatsappNumber(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/[?&]phone=([^&]+)/i', $value, $matches) === 1) {
            $value = urldecode($matches[1]);
        } elseif (preg_match('~wa\.me/([^?/\s]+)~i', $value, $matches) === 1) {
            $value = urldecode($matches[1]);
        }

        $hasLeadingPlus = str_starts_with($value, '+');
        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === '') {
            return null;
        }

        return $hasLeadingPlus ? "+{$digits}" : $digits;
    }

    public static function buildWhatsappUrl(?string $value): ?string
    {
        $number = static::normalizeWhatsappNumber($value);

        if ($number === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $number);

        return $digits === '' ? null : "https://wa.me/{$digits}";
    }

    public static function normalizeSocialIcon(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));

        if ($value === '') {
            return null;
        }

        $aliases = [
            'linkedin' => 'linkedin',
            'linked in' => 'linkedin',
            'instagram' => 'instagram',
            'insta' => 'instagram',
            'facebook' => 'facebook',
            'fb' => 'facebook',
            'behance' => 'behance',
            'x' => 'x',
            'twitter' => 'x',
            'youtube' => 'youtube',
            'you tube' => 'youtube',
            'yt' => 'youtube',
            'tiktok' => 'tiktok',
            'tik tok' => 'tiktok',
            'github' => 'github',
            'git hub' => 'github',
            'whatsapp' => 'whatsapp',
            'whats app' => 'whatsapp',
            'website' => 'globe',
            'web' => 'globe',
            'globe' => 'globe',
            'site' => 'globe',
        ];

        return $aliases[$value] ?? null;
    }

    /**
     * @param  mixed  $links
     * @return array<int, array{icon:string,url:string}>
     */
    public static function normalizeSocialLinks(mixed $links): array
    {
        if (! is_array($links)) {
            return [];
        }

        return collect($links)
            ->map(function ($link): ?array {
                if (! is_array($link)) {
                    return null;
                }

                $icon = static::normalizeSocialIcon($link['icon'] ?? $link['label'] ?? null);
                $url = trim((string) ($link['url'] ?? ''));

                if ($icon === null || $url === '') {
                    return null;
                }

                return [
                    'icon' => $icon,
                    'url' => $url,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
