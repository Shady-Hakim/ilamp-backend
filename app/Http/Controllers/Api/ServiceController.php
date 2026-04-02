<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    public function index(): JsonResponse
    {
        $services = Service::query()
            ->where('is_published', true)
            ->orderBy('title')
            ->get();

        return response()->json(
            $services->map(fn (Service $service): array => [
                'id' => $service->id,
                'slug' => $service->slug,
                'title' => $service->title,
                'desc' => $service->short_description,
                'iconKey' => $service->icon_key,
                'features' => $service->features ?? [],
            ])->values()
        );
    }

    public function show(string $slug): JsonResponse
    {
        $service = Service::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return response()->json([
            'id' => $service->id,
            'slug' => $service->slug,
            'title' => $service->title,
            'desc' => $service->short_description,
            'iconKey' => $service->icon_key,
            'features' => $service->features ?? [],
            'headline' => $service->headline,
            'subheadline' => $service->subheadline,
            'description' => $service->description,
            'benefits' => $service->benefits ?? [],
            'process' => $service->process_steps ?? [],
            'faqs' => $service->faq_items ?? [],
            'seoTitle' => $service->seo_title,
            'seoDescription' => $service->seo_description,
        ]);
    }
}
