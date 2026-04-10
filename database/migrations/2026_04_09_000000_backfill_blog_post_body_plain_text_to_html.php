<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Converts blog_posts.body fields that contain plain text (no HTML tags) into
 * minimal HTML by wrapping each double-newline-separated paragraph in <p> tags.
 *
 * Posts that already contain HTML (e.g. saved via the Quill WYSIWYG editor)
 * are left untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('blog_posts')
            ->select(['id', 'body'])
            ->orderBy('id')
            ->get()
            ->each(function (object $post): void {
                if (blank($post->body)) {
                    return;
                }

                // Skip rows that already contain HTML markup.
                if (preg_match('/<\/?[a-z][\s\S]*>/i', $post->body)) {
                    return;
                }

                DB::table('blog_posts')
                    ->where('id', $post->id)
                    ->update([
                        'body' => $this->plainTextToHtml($post->body),
                    ]);
            });
    }

    public function down(): void
    {
        // Irreversible data backfill — plain text originals are not retained.
    }

    private function plainTextToHtml(string $body): string
    {
        $body = trim($body);

        // Split on one or more blank lines (paragraph breaks).
        $paragraphs = preg_split('/\n{2,}/', $body) ?: [];

        $html = '';
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }

            // Escape HTML entities, then restore single newlines as <br>.
            $escaped = htmlspecialchars($paragraph, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $escaped = str_replace("\n", '<br>', $escaped);

            $html .= '<p>' . $escaped . '</p>' . "\n";
        }

        return rtrim($html);
    }
};
