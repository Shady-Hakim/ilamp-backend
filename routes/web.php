<?php

use App\Http\Controllers\FrontendExportController;
use App\Http\Controllers\RunMigrationsController;
use App\Http\Controllers\StorageProxyController;
use Illuminate\Support\Facades\Route;

// TEMPORARY — remove after running migrations on production.
// Throttled to 3 requests/min; token is required via MIGRATION_TOKEN env var.
Route::middleware('throttle:3,1')->get('/__run-migrations', RunMigrationsController::class);

// Serves storage/app/public/* when public/storage symlink is unavailable (shared hosts).
Route::get('/storage/{path}', StorageProxyController::class)->where('path', '.+');

Route::get('/', function () {
    if (is_dir(public_path('_next'))) {
        return app(FrontendExportController::class)(request());
    }

    return redirect('/admin');
});

Route::fallback(FrontendExportController::class);
