<?php

declare(strict_types=1);

use App\Models\BlogPost;
use App\Services\FrontendStaticRouteService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\File;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$publishedSlugs = BlogPost::query()
    ->where('is_published', true)
    ->get()
    ->pluck('slug')
    ->filter()
    ->values();

$manifestPath = public_path('.frontend-export-manifest.json');
$exportedSlugs = collect();

if (File::exists($manifestPath)) {
    $manifestFiles = json_decode((string) File::get($manifestPath), true);

    if (is_array($manifestFiles)) {
        $exportedSlugs = collect($manifestFiles)
            ->filter(fn (mixed $path): bool => is_string($path))
            ->map(function (string $path): ?string {
                if (! preg_match('#^blog/([^/]+)/index\.html$#', $path, $matches)) {
                    return null;
                }

                return $matches[1] ?? null;
            })
            ->filter(fn (?string $slug): bool => filled($slug) && $slug !== 'category')
            ->values();
    }
}

$keepSlugs = $publishedSlugs->merge($exportedSlugs)->unique()->values();

collect(File::directories(public_path('blog')))
    ->reject(fn (string $directory): bool => basename($directory) === 'category')
    ->reject(fn (string $directory): bool => $keepSlugs->contains(basename($directory)))
    ->each(fn (string $directory): bool => File::deleteDirectory($directory));

BlogPost::query()
    ->where('is_published', true)
    ->get()
    ->each(fn (BlogPost $post) => app(FrontendStaticRouteService::class)->syncBlogPost($post));

fwrite(STDOUT, "Synced published blog static routes.\n");
