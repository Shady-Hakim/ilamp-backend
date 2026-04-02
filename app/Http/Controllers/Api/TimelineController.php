<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimelineItem;
use Illuminate\Http\JsonResponse;

class TimelineController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $items = TimelineItem::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('year')
            ->get();

        return response()->json(
            $items->map(fn (TimelineItem $item): array => [
                'id' => $item->id,
                'year' => $item->year,
                'title' => $item->title,
                'desc' => $item->description,
            ])->values()
        );
    }
}
