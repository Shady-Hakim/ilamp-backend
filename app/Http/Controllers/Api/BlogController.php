<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\MediaLibraryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function __construct(
        protected MediaLibraryService $mediaLibraryService,
    ) {
    }

    public function categories(): JsonResponse
    {
        $categories = BlogCategory::query()
            ->where('is_published', true)
            ->orderBy('name')
            ->get();

        return response()->json(
            $categories->map(fn (BlogCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'iconKey' => $category->icon_key,
            ])->values()
        );
    }

    public function index(Request $request): JsonResponse
    {
        $query = BlogPost::query()
            ->where('is_published', true)
            ->with(['categories' => fn ($relation) => $relation->where('is_published', true)])
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at');

        if ($categorySlug = $request->string('category')->toString()) {
            $query->whereHas('categories', fn ($relation) => $relation->where('slug', $categorySlug));
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('title', 'like', '%'.$search.'%')
                    ->orWhere('excerpt', 'like', '%'.$search.'%')
                    ->orWhere('body', 'like', '%'.$search.'%');
            });
        }

        return response()->json(
            $query->get()->map(fn (BlogPost $post): array => $this->transformPost($post, $request))->values()
        );
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $post = BlogPost::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with(['categories' => fn ($relation) => $relation->where('is_published', true)])
            ->firstOrFail();

        return response()->json($this->transformPost($post, $request));
    }

    protected function transformPost(BlogPost $post, Request $request): array
    {
        $imageMeta = $this->mediaLibraryService->toApiMedia(
            $post->image_resolved_url,
            $request->getSchemeAndHttpHost(),
        );

        return [
            'id' => $post->id,
            'slug' => $post->slug,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'description' => $post->body,
            'author' => filled($post->author_name) ? $post->author_name : 'iLamp Team',
            'date' => optional($post->published_at ?: $post->created_at)->format('M j, Y'),
            'featured' => $post->is_featured,
            'imageUrl' => $imageMeta['url'] ?? null,
            'imageMeta' => $imageMeta,
            'categories' => $post->categories->pluck('id')->values(),
            'categoryMeta' => $post->categories->map(fn (BlogCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'iconKey' => $category->icon_key,
            ])->values(),
        ];
    }
}
