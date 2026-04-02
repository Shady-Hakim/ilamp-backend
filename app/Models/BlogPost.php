<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (BlogPost $post): void {
            if (blank($post->excerpt)) {
                $post->excerpt = static::generateExcerpt($post->body);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(BlogCategory::class, 'blog_category_post')->withTimestamps();
    }

    public static function generateExcerpt(?string $body, int $limit = 220): ?string
    {
        $body = trim((string) $body);

        if ($body === '') {
            return null;
        }

        $textWithBreaks = preg_replace(
            '/<\s*br\s*\/?\s*>|<\/\s*(p|div|li|blockquote|h[1-6])\s*>/i',
            PHP_EOL,
            $body,
        );

        $plainText = html_entity_decode(
            strip_tags((string) $textWithBreaks),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );

        $lines = array_values(array_filter(array_map(
            static fn (string $line): string => preg_replace('/\s+/', ' ', trim($line)) ?: '',
            preg_split('/\R+/', $plainText) ?: [],
        )));

        if ($lines === []) {
            return null;
        }

        return Str::limit(implode(' ', array_slice($lines, 0, 2)), $limit, '...');
    }
}
