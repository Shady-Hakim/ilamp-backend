<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\PortfolioProject;
use App\Services\FrontendStaticRouteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class FrontendExportController extends Controller
{
    public function __invoke(Request $request, string $path = ''): mixed
    {
        $path = trim($path, '/');

        if ($path !== '' && str_contains($path, '.')) {
            abort(404);
        }

        $candidateFiles = array_values(array_filter([
            $path === '' ? public_path('index.html') : null,
            $path !== '' ? public_path($path.'.html') : null,
            $path !== '' ? public_path($path.DIRECTORY_SEPARATOR.'index.html') : null,
        ]));

        foreach ($candidateFiles as $candidate) {
            if (File::exists($candidate)) {
                return response(File::get($candidate), 200, [
                    'Content-Type' => 'text/html; charset=UTF-8',
                ]);
            }
        }

        if ($dynamicHtml = $this->resolveDynamicFrontendHtml($request, $path)) {
            return response($dynamicHtml, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        $notFoundFile = public_path('404.html');

        if (File::exists($notFoundFile)) {
            return response(File::get($notFoundFile), 404, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        abort(404);
    }

    protected function resolveDynamicFrontendHtml(Request $request, string $path): ?string
    {
        if (preg_match('#^blog/([^/]+)$#', $path, $matches) === 1) {
            return $this->renderBlogPostHtml($request, $matches[1]);
        }

        if (preg_match('#^portfolio/([^/]+)$#', $path, $matches) === 1) {
            return $this->renderPortfolioProjectHtml($request, $matches[1]);
        }

        return null;
    }

    protected function renderPortfolioProjectHtml(Request $request, string $slug): ?string
    {
        $project = PortfolioProject::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();

        if (! $project) {
            return null;
        }

        $templatePath = app(FrontendStaticRouteService::class)->portfolioProjectTemplatePath();

        if (! $templatePath) {
            return null;
        }

        app(FrontendStaticRouteService::class)->syncPortfolioProject($project);

        return app(FrontendStaticRouteService::class)->renderPortfolioProjectFile(
            File::get($templatePath),
            basename(dirname($templatePath)),
            $project,
            rtrim($request->getSchemeAndHttpHost(), '/'),
        );
    }

    protected function renderBlogPostHtml(Request $request, string $slug): ?string
    {
        $post = BlogPost::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();

        if (! $post) {
            return null;
        }

        $templatePath = app(FrontendStaticRouteService::class)->blogPostTemplatePath();

        if (! $templatePath) {
            return null;
        }

        app(FrontendStaticRouteService::class)->syncBlogPost($post);

        return app(FrontendStaticRouteService::class)->renderBlogPostFile(
            File::get($templatePath),
            basename(dirname($templatePath)),
            $post,
            rtrim($request->getSchemeAndHttpHost(), '/'),
        );
    }

}
