<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with(['sections' => fn ($query) => $query->where('is_enabled', true)->orderBy('sort_order')])
            ->firstOrFail();

        return response()->json([
            'id' => $page->id,
            'slug' => $page->slug,
            'title' => $page->title,
            'metaTitle' => $page->meta_title,
            'metaDescription' => $page->meta_description,
            'sections' => $page->sections->map(fn ($section): array => [
                'id' => $section->id,
                'key' => $section->key,
                'type' => $section->type,
                'content' => $this->normalizeSectionContent(
                    $section->type,
                    $section->content ?? [],
                ),
                'sortOrder' => $section->sort_order,
            ])->values(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    protected function normalizeSectionContent(string $type, array $content): array
    {
        if (($type !== 'cards') || ! is_array($content['items'] ?? null)) {
            return $content;
        }

        $content['items'] = array_map(function (mixed $item): mixed {
            if (! is_array($item)) {
                return $item;
            }

            if (blank($item['description'] ?? null) && filled($item['desc'] ?? null)) {
                $item['description'] = $item['desc'];
            }

            return $item;
        }, $content['items']);

        return $content;
    }
}
