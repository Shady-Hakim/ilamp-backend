<?php

use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\ConsultationController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\SiteSettingController;
use App\Http\Controllers\Api\TestimonialController;
use App\Http\Controllers\Api\TimelineController;
use Illuminate\Support\Facades\Route;

Route::get('/site-settings', SiteSettingController::class);
Route::get('/pages/{slug}', [PageController::class, 'show']);

Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{slug}', [ServiceController::class, 'show']);

Route::get('/portfolio/categories', [PortfolioController::class, 'categories']);
Route::get('/portfolio/projects', [PortfolioController::class, 'index']);
Route::get('/portfolio/projects/{slug}', [PortfolioController::class, 'show']);

Route::get('/blog/categories', [BlogController::class, 'categories']);
Route::get('/blog/posts', [BlogController::class, 'index']);
Route::get('/blog/posts/{slug}', [BlogController::class, 'show']);

Route::get('/about/timeline', TimelineController::class);
Route::get('/testimonials', TestimonialController::class);

Route::get('/consultation/availability', [ConsultationController::class, 'availability']);
Route::get('/consultation/slots', [ConsultationController::class, 'slots']);
Route::post('/consultation/reservations', [ConsultationController::class, 'store']);

Route::post('/contact', [ContactController::class, 'store']);
