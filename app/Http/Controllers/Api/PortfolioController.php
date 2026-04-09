<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PortfolioCategory;
use App\Models\PortfolioProject;
use App\Services\MediaLibraryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function __construct(
        protected MediaLibraryService $mediaLibraryService,
    ) {
    }

    public function categories(): JsonResponse
    {
        $categories = PortfolioCategory::query()
            ->where('is_published', true)
            ->orderBy('name')
            ->get();

        return response()->json(
            $categories->map(fn (PortfolioCategory $category): array => [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->name,
                'description' => $category->description,
                'iconKey' => $category->icon_key,
            ])->values()
        );
    }

    public function index(Request $request): JsonResponse
    {
        $query = PortfolioProject::query()
            ->where('is_published', true)
            ->with(['categories' => fn ($relation) => $relation->where('is_published', true)])
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->orderByDesc('year')
            ->orderBy('title');

        if ($categorySlug = mb_substr($request->string('category')->toString(), 0, 255)) {
            $query->whereHas('categories', fn ($relation) => $relation->where('slug', $categorySlug));
        }

        if ($request->filled('page')) {
            $perPage = max(1, min(50, (int) $request->input('per_page', 9)));
            $paginated = $query->paginate($perPage);
            $paginated->setCollection(
                $paginated->getCollection()
                    ->map(fn (PortfolioProject $project): array => $this->transformProject($project, $request))
            );

            return response()->json($paginated);
        }

        return response()->json(
            $query->get()->map(fn (PortfolioProject $project): array => $this->transformProject($project, $request))->values()
        );
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $project = PortfolioProject::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with(['categories' => fn ($relation) => $relation->where('is_published', true)])
            ->firstOrFail();

        return response()->json($this->transformProject($project, $request));
    }

    protected function transformProject(PortfolioProject $project, Request $request): array
    {
        $origin = $request->getSchemeAndHttpHost();
        $imageMeta = $this->mediaLibraryService->toApiMedia($project->image_resolved_url, $origin);
        $clientLogoMeta = $this->mediaLibraryService->toApiMedia($project->client_logo_resolved_url, $origin);
        $galleryMeta = collect($project->gallery_resolved_urls)
            ->map(fn (string $image): ?array => $this->mediaLibraryService->toApiMedia($image, $origin))
            ->filter()
            ->values();

        return [
            'id' => $project->id,
            'slug' => $project->slug,
            'title' => $project->title,
            'desc' => $project->short_description,
            'brief' => $project->brief,
            'liveUrl' => $project->project_url,
            'tech' => $project->tech_stack ?? [],
            'imageUrl' => $imageMeta['url'] ?? null,
            'imageMeta' => $imageMeta,
            'client' => $project->client,
            'clientBrief' => $project->client_brief,
            'clientLogo' => $clientLogoMeta['url'] ?? null,
            'clientLogoMeta' => $clientLogoMeta,
            'year' => $project->year,
            'challenge' => $project->challenge,
            'solution' => $project->solution,
            'results' => $project->results ?? [],
            'gallery' => $galleryMeta->pluck('url')->all(),
            'galleryMeta' => $galleryMeta->all(),
            'categories' => $project->categories->map(fn (PortfolioCategory $category): array => [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->name,
                'description' => $category->description,
                'iconKey' => $category->icon_key,
            ])->values(),
        ];
    }
}
