<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::post('/s/{slug}', [SiteController::class, 'unlock'])->name('site.unlock');
Route::get('/s/{slug}/{path?}', [SiteController::class, 'show'])->where('path', '.*')->name('site.show');
