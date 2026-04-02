<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('blog_posts')
            ->select(['id', 'body'])
            ->orderBy('id')
            ->get()
            ->each(function (object $post): void {
                DB::table('blog_posts')
                    ->where('id', $post->id)
                    ->update([
                        'excerpt' => $this->generateExcerpt($post->body),
                    ]);
            });
    }

    public function down(): void
    {
        // Irreversible data backfill.
    }

    protected function generateExcerpt(?string $body, int $limit = 220): ?string
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
};
