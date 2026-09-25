<?php

use App\Http\Controllers\Admin\SiteController as AdminSiteController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/sites');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy']);
    Route::get('/sites', [AdminSiteController::class, 'index']);
    Route::post('/sites', [AdminSiteController::class, 'store']);
    Route::get('/sites/{site}', [AdminSiteController::class, 'show']);
    Route::patch('/sites/{site}', [AdminSiteController::class, 'update']);
    Route::delete('/sites/{site}', [AdminSiteController::class, 'destroy']);
    Route::post('/sites/{site}/files', [AdminSiteController::class, 'upload']);
    Route::delete('/sites/{site}/files', [AdminSiteController::class, 'deleteFile']);
});

Route::post('/s/{slug}', [SiteController::class, 'unlock'])->name('site.unlock');
Route::get('/s/{slug}/{path?}', [SiteController::class, 'show'])->where('path', '.*')->name('site.show');
Route::get('/p/{signature}/{slug}/{path?}', [SiteController::class, 'preview'])->where('path', '.*');
