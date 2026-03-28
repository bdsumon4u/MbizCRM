<?php

use App\Http\Controllers\Admin\FacebookBusinessManagerOAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/privacy-policy', 'legal.privacy-policy')->name('legal.privacy-policy');
Route::view('/terms-of-service', 'legal.terms-of-service')->name('legal.terms-of-service');
Route::view('/user-data-deletion', 'legal.privacy-policy')->name('legal.user-data-deletion');

Route::middleware(['auth:admin'])->prefix('admin/facebook/business-managers/oauth')->group(function (): void {
    Route::get('/redirect', [FacebookBusinessManagerOAuthController::class, 'redirect'])
        ->name('admin.facebook.business-managers.oauth.redirect');
    Route::get('/callback', [FacebookBusinessManagerOAuthController::class, 'callback'])
        ->name('admin.facebook.business-managers.oauth.callback');
});
