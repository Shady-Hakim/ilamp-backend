<?php

use App\Http\Controllers\FrontendExportController;
use App\Http\Controllers\StorageProxyController;
use Illuminate\Support\Facades\Route;

// Serves storage/app/public/* when public/storage symlink is unavailable (shared hosts).
Route::get('/storage/{path}', StorageProxyController::class)->where('path', '.+');

Route::get('/', function () {
    if (file_exists(public_path('index.html'))) {
        return app(FrontendExportController::class)(request());
    }

    return redirect('/admin');
});

Route::fallback(FrontendExportController::class);
