<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SiteSetting::query()
            ->get()
            ->each(function (SiteSetting $setting): void {
                $normalizedLinks = SiteSetting::normalizeSocialLinks($setting->social_links);

                if ($normalizedLinks === ($setting->social_links ?? [])) {
                    return;
                }

                $setting->forceFill([
                    'social_links' => $normalizedLinks,
                ])->save();
            });
    }

    public function down(): void
    {
        SiteSetting::query()
            ->get()
            ->each(function (SiteSetting $setting): void {
                $legacyLinks = collect(SiteSetting::normalizeSocialLinks($setting->social_links))
                    ->map(fn (array $link): array => [
                        'label' => SiteSetting::socialIconOptions()[$link['icon']] ?? ucfirst($link['icon']),
                        'url' => $link['url'],
                    ])
                    ->all();

                $setting->forceFill([
                    'social_links' => $legacyLinks,
                ])->save();
            });
    }
};
