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
            ->where('key', 'intro')
            ->where('type', 'content')
            ->first();

        if (! $section) {
            return;
        }

        $content = json_decode((string) $section->content, true);

        if (! is_array($content)) {
            return;
        }

        $description = trim((string) ($content['description'] ?? ''));

        if ($description === '') {
            $description = $this->paragraphsToHtml($content['items'] ?? null);
        }

        unset($content['items']);
        $content['description'] = $description;

        DB::table('page_sections')
            ->where('id', $section->id)
            ->update([
                'content' => json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No-op: this migration consolidates legacy intro items into description HTML.
    }

    protected function paragraphsToHtml(mixed $items): string
    {
        if (! is_array($items)) {
            return '';
        }

        $paragraphs = array_values(array_filter(array_map(
            static fn (mixed $item): string => trim((string) data_get($item, 'text')),
            $items,
        )));

        if ($paragraphs === []) {
            return '';
        }

        return implode(PHP_EOL, array_map(
            static function (string $paragraph): string {
                $escaped = htmlspecialchars($paragraph, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                return '<p>' . nl2br($escaped, false) . '</p>';
            },
            $paragraphs,
        ));
    }
};
