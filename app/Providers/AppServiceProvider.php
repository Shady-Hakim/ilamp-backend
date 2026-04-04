<?php

namespace App\Providers;

use App\Models\BlogPost;
use App\Models\MediaAsset;
use App\Models\PortfolioProject;
use App\Observers\BlogPostObserver;
use App\Observers\MediaAssetObserver;
use App\Observers\PortfolioProjectObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        BlogPost::observe(BlogPostObserver::class);
        PortfolioProject::observe(PortfolioProjectObserver::class);
        MediaAsset::observe(MediaAssetObserver::class);
    }
}
