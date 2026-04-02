<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;

class TestimonialController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $testimonials = Testimonial::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json(
            $testimonials->map(fn (Testimonial $testimonial): array => [
                'id' => 'testimonial-'.$testimonial->id,
                'name' => $testimonial->name,
                'role' => $testimonial->role,
                'quote' => $testimonial->quote,
            ])->values()
        );
    }
}
