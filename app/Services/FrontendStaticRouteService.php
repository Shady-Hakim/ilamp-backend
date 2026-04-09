<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\PortfolioProject;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FrontendStaticRouteService
{
    public function portfolioProjectTemplatePath(): ?string
    {
        return $this->exportedTemplatePath(
            'portfolio',
            '#^portfolio/[^/]+/index\.html$#',
            ['portfolio/category/'],
            fn (): ?string => $this->legacyPortfolioProjectTemplatePath(),
        );
    }

    public function blogPostTemplatePath(): ?string
    {
        return $this->exportedTemplatePath(
            'blog',
            '#^blog/[^/]+/index\.html$#',
            ['blog/category/'],
            fn (): ?string => $this->legacyBlogPostTemplatePath(),
        );
    }

    public function syncPortfolioProject(PortfolioProject $project): void
    {
        if (! $project->is_published || blank($project->slug)) {
            return;
        }

        $templatePath = $this->portfolioProjectTemplatePath();

        if (! $templatePath) {
            return;
        }

        $templateDirectory = dirname($templatePath);
        $targetDirectory = public_path('portfolio'.DIRECTORY_SEPARATOR.$project->slug);

        File::ensureDirectoryExists($targetDirectory);

        foreach (['index.html', 'index.txt'] as $fileName) {
            $templatePath = $templateDirectory.DIRECTORY_SEPARATOR.$fileName;

            if (! File::exists($templatePath)) {
                continue;
            }

            $rendered = $this->renderPortfolioProjectFile(
                File::get($templatePath),
                basename($templateDirectory),
                $project,
            );

            File::put($targetDirectory.DIRECTORY_SEPARATOR.$fileName, $rendered);
        }
    }

    public function syncBlogPost(BlogPost $post): void
    {
        if (! $post->is_published || blank($post->slug)) {
            return;
        }

        $templatePath = $this->blogPostTemplatePath();

        if (! $templatePath) {
            return;
        }

        $templateDirectory = dirname($templatePath);
        $targetDirectory = public_path('blog'.DIRECTORY_SEPARATOR.$post->slug);

        File::ensureDirectoryExists($targetDirectory);

        foreach (['index.html', 'index.txt'] as $fileName) {
            $templatePath = $templateDirectory.DIRECTORY_SEPARATOR.$fileName;

            if (! File::exists($templatePath)) {
                continue;
            }

            $rendered = $this->renderBlogPostFile(
                File::get($templatePath),
                basename($templateDirectory),
                $post,
            );

            File::put($targetDirectory.DIRECTORY_SEPARATOR.$fileName, $rendered);
        }
    }

    public function renderPortfolioProjectFile(
        string $template,
        string $templateSlug,
        PortfolioProject $project,
        ?string $absoluteUrl = null,
    ): string {
        $title = trim($project->title).' — Case Study | iLamp Agency';
        $description = trim((string) ($project->brief ?: $project->short_description ?: "Explore {$project->title} by iLamp Agency."));
        $url = rtrim($absoluteUrl ?: (string) config('app.url'), '/')."/portfolio/{$project->slug}/";

        $replacements = [
            $templateSlug => $project->slug,
        ];

        $replacementPairs = [
            [$this->firstMatch('/<title>(.*?)<\/title>/i', $template), $title],
            [$this->firstMatch('/<meta name="description" content="([^"]*)"/i', $template), $description],
            [$this->firstMatch('/<link rel="canonical" href="([^"]*)"/i', $template), $url],
            [$this->firstMatch('/<meta property="og:url" content="([^"]*)"/i', $template), $url],
            [$this->firstMatch('/<meta property="og:title" content="([^"]*)"/i', $template), $title],
            [$this->firstMatch('/<meta property="og:description" content="([^"]*)"/i', $template), $description],
            [$this->firstMatch('/<meta name="twitter:title" content="([^"]*)"/i', $template), $title],
            [$this->firstMatch('/<meta name="twitter:description" content="([^"]*)"/i', $template), $description],
            [$this->firstMatch('/<meta property="og:image:alt" content="([^"]*)"/i', $template), $title],
        ];

        foreach ($replacementPairs as [$from, $to]) {
            if (! filled($from)) {
                continue;
            }

            $replacements = array_merge($replacements, $this->replacementVariants($from, $to));
        }

        return strtr($template, $replacements);
    }

    public function renderBlogPostFile(
        string $template,
        string $templateSlug,
        BlogPost $post,
        ?string $absoluteUrl = null,
    ): string {
        $title = trim($post->title).' | iLamp Agency Blog';
        $description = trim((string) ($post->excerpt ?: Str::limit(strip_tags((string) $post->body), 160, '...') ?: "Read {$post->title} on the iLamp Agency Blog."));
        $url = rtrim($absoluteUrl ?: (string) config('app.url'), '/')."/blog/{$post->slug}/";

        $replacements = [
            $templateSlug => $post->slug,
        ];

        $replacementPairs = [
            [$this->firstMatch('/<title>(.*?)<\/title>/i', $template), $title],
            [$this->firstMatch('/<meta name="description" content="([^"]*)"/i', $template), $description],
            [$this->firstMatch('/<link rel="canonical" href="([^"]*)"/i', $template), $url],
            [$this->firstMatch('/<meta property="og:url" content="([^"]*)"/i', $template), $url],
            [$this->firstMatch('/<meta property="og:title" content="([^"]*)"/i', $template), $title],
            [$this->firstMatch('/<meta property="og:description" content="([^"]*)"/i', $template), $description],
            [$this->firstMatch('/<meta name="twitter:title" content="([^"]*)"/i', $template), $title],
            [$this->firstMatch('/<meta name="twitter:description" content="([^"]*)"/i', $template), $description],
            [$this->firstMatch('/<meta property="og:image:alt" content="([^"]*)"/i', $template), $title],
        ];

        foreach ($replacementPairs as [$from, $to]) {
            if (! filled($from)) {
                continue;
            }

            $replacements = array_merge($replacements, $this->replacementVariants($from, $to));
        }

        return strtr($template, $replacements);
    }

    protected function legacyPortfolioProjectTemplatePath(): ?string
    {
        $templates = glob(public_path('portfolio/*/index.html')) ?: [];

        foreach ($templates as $templatePath) {
            if (str_contains($templatePath, DIRECTORY_SEPARATOR.'category'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            return $templatePath;
        }

        return null;
    }

    protected function legacyBlogPostTemplatePath(): ?string
    {
        $templates = glob(public_path('blog/*/index.html')) ?: [];

        foreach ($templates as $templatePath) {
            if (str_contains($templatePath, DIRECTORY_SEPARATOR.'category'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            return $templatePath;
        }

        return null;
    }

    protected function exportedTemplatePath(
        string $rootDirectory,
        string $pattern,
        array $excludedFragments,
        callable $fallback,
    ): ?string {
        $manifestPath = public_path('.frontend-export-manifest.json');

        if (File::exists($manifestPath)) {
            $files = json_decode((string) File::get($manifestPath), true);

            if (is_array($files)) {
                foreach ($files as $relativePath) {
                    if (! is_string($relativePath)) {
                        continue;
                    }

                    if (! preg_match($pattern, $relativePath)) {
                        continue;
                    }

                    $isExcluded = collect($excludedFragments)
                        ->contains(fn (string $fragment): bool => str_contains($relativePath, $fragment));

                    if ($isExcluded) {
                        continue;
                    }

                    $templatePath = public_path($relativePath);

                    if (File::exists($templatePath) && str_starts_with($relativePath, $rootDirectory.'/')) {
                        return $templatePath;
                    }
                }
            }
        }

        return $fallback();
    }

    protected function firstMatch(string $pattern, string $content): ?string
    {
        preg_match($pattern, $content, $matches);

        return $matches[1] ?? null;
    }

    /**
     * @return array<string, string>
     */
    protected function replacementVariants(string $from, string $to): array
    {
        $variants = [];

        $fromJson = $this->jsonStringContent($from);
        $toJson = $this->jsonStringContent($to);

        foreach ([
            $from => $to,
            htmlspecialchars($from, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                => htmlspecialchars($to, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $fromJson => $toJson,
        ] as $search => $replace) {
            if ($search === '') {
                continue;
            }

            $variants[$search] = $replace;
        }

        return $variants;
    }

    protected function jsonStringContent(string $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

        // json_encode returns false on malformed UTF-8. Sanitize and retry rather than
        // returning the raw string, which can contain literal newlines that break the
        // line-based RSC payload (.txt) format and cause "Unterminated string" errors.
        if (! is_string($encoded)) {
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
        }

        if (! is_string($encoded)) {
            return '';
        }

        return trim($encoded, '"');
    }
}
