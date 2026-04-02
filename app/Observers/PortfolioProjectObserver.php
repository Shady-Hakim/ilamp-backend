<?php

namespace App\Observers;

use App\Models\PortfolioProject;
use App\Services\FrontendStaticRouteService;
use App\Services\MediaLibraryService;

class PortfolioProjectObserver
{
    public function saved(PortfolioProject $project): void
    {
        app(MediaLibraryService::class)->syncPortfolioProject($project);
        app(FrontendStaticRouteService::class)->syncPortfolioProject($project);
    }
}
