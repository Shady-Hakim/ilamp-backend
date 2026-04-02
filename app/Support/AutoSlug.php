<?php

namespace App\Support;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

class AutoSlug
{
    public static function sync(Get $get, Set $set, mixed $old, mixed $state): void
    {
        $currentSlug = trim((string) $get('slug'));
        $previousGeneratedSlug = static::slugify($old);

        if ($currentSlug !== '' && $currentSlug !== $previousGeneratedSlug) {
            return;
        }

        $set('slug', static::slugify($state));
    }

    public static function resolve(mixed $slug, mixed $source): string
    {
        $slug = trim((string) $slug);

        if ($slug !== '') {
            return static::slugify($slug);
        }

        return static::slugify($source);
    }

    protected static function slugify(mixed $value): string
    {
        return Str::slug((string) $value);
    }
}
