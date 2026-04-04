<?php

namespace App\Observers;

use App\Models\PortfolioProject;
use App\Services\FrontendStaticRouteService;

class PortfolioProjectObserver
{
    public function saved(PortfolioProject $project): void
    {
        try {
            app(FrontendStaticRouteService::class)->syncPortfolioProject($project);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
