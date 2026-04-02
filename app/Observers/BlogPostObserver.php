<?php

namespace App\Observers;

use App\Models\BlogPost;
use App\Services\FrontendStaticRouteService;
use App\Services\MediaLibraryService;

class BlogPostObserver
{
    public function saved(BlogPost $blogPost): void
    {
        app(MediaLibraryService::class)->syncBlogPost($blogPost);
        app(FrontendStaticRouteService::class)->syncBlogPost($blogPost);
    }
}
