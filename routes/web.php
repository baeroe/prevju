<?php

use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\SiteController as AdminSiteController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// admin: the language is part of the URL (/en/sites, /de/sites)
Route::prefix('{locale}')->where(['locale' => 'en|de'])->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'show'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [LoginController::class, 'destroy']);
        Route::get('/sites', [AdminSiteController::class, 'index'])->name('sites.index');
        Route::post('/sites', [AdminSiteController::class, 'store']);
        Route::get('/sites/{site}', [AdminSiteController::class, 'show'])->name('sites.show');
        Route::patch('/sites/{site}', [AdminSiteController::class, 'update']);
        Route::delete('/sites/{site}', [AdminSiteController::class, 'destroy']);
        Route::post('/sites/{site}/files', [AdminSiteController::class, 'upload']);
        Route::delete('/sites/{site}/files', [AdminSiteController::class, 'deleteFile']);
        Route::get('/projects', [AdminProjectController::class, 'index'])->name('projects.index');
        Route::post('/projects', [AdminProjectController::class, 'store']);
        Route::get('/projects/{project}', [AdminProjectController::class, 'show'])->name('projects.show');
        Route::patch('/projects/{project}', [AdminProjectController::class, 'update']);
        Route::delete('/projects/{project}', [AdminProjectController::class, 'destroy']);
        Route::get('/tokens', [TokenController::class, 'index'])->name('tokens.index');
        Route::post('/tokens', [TokenController::class, 'store']);
        Route::delete('/tokens/{token}', [TokenController::class, 'destroy']);
    });
});

// bookmarks from before the language prefix: / and /sites/3 go to /<last language or en>/…
Route::get('/{path?}', fn (string $path = 'projects') => redirect('/'.app()->getLocale().'/'.$path))
    ->where('path', '(sites|projects|tokens|login)(/.*)?');

// language switch for the client pages, which keep their URLs
Route::get('/locale/{lang}', LocaleController::class)->name('locale');

// signed URL handed out by the MCP tool get_upload_url
Route::post('/upload/{site}', UploadController::class)->middleware('signed')->name('upload');

// own throttle prefix so unlock attempts don't share the /login budget
Route::post('/s/{slug}', [SiteController::class, 'unlock'])->middleware('throttle:10,1,unlock')->name('site.unlock');
Route::get('/s/{slug}/{path?}', [SiteController::class, 'show'])->where('path', '.*')->name('site.show');
Route::get('/p/{signature}/{slug}/{path?}', [SiteController::class, 'preview'])->where('path', '.*');
Route::post('/project/{slug}', [ProjectController::class, 'unlock'])->middleware('throttle:10,1,unlock')->name('project.unlock');
Route::get('/project/{slug}', [ProjectController::class, 'show'])->name('project.show');
