<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\LandingPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingPageController::class, 'index'])->name('site.home');
Route::get('/products/{slug}', [LandingPageController::class, 'showProduct'])->name('site.products.show');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/stores', [AdminDashboardController::class, 'stores'])->name('stores');
    Route::get('/subscriptions', [AdminDashboardController::class, 'subscriptions'])->name('subscriptions');
    Route::get('/payments', [AdminDashboardController::class, 'payments'])->name('payments');
    Route::get('/shipping', [AdminDashboardController::class, 'shipping'])->name('shipping');
    Route::get('/analytics', [AdminDashboardController::class, 'analytics'])->name('analytics');
    Route::get('/partners', [AdminDashboardController::class, 'partners'])->name('partners');
    Route::get('/support', [AdminDashboardController::class, 'support'])->name('support');
    Route::get('/apps', [AdminDashboardController::class, 'apps'])->name('apps');
    Route::get('/site-content', [AdminDashboardController::class, 'siteContent'])->name('site-content');
    Route::post('/site-content', [AdminDashboardController::class, 'updateSiteContent'])->name('site-content.update');
    Route::get('/settings', [AdminDashboardController::class, 'settings'])->name('settings');
});