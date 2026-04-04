<?php

namespace App\Observers;

use App\Models\BlogPost;
use App\Services\FrontendStaticRouteService;

class BlogPostObserver
{
    public function saved(BlogPost $blogPost): void
    {
        try {
            app(FrontendStaticRouteService::class)->syncBlogPost($blogPost);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
