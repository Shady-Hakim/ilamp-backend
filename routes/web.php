<?php

use App\Http\Controllers\FrontendExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (file_exists(public_path('index.html'))) {
        return app(FrontendExportController::class)(request());
    }

    return redirect('/admin');
});

Route::fallback(FrontendExportController::class);
