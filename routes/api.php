<?php

use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\VisitController;
use Illuminate\Support\Facades\Route;

// same Sanctum tokens as /mcp, managed under /tokens; names prefixed so they don't shadow the admin's sites.show etc.
Route::middleware(['auth:sanctum', 'throttle:api'])->name('api.')->group(function () {
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('sites', SiteController::class);
    Route::get('/sites/{site}/files', [FileController::class, 'index']);
    Route::post('/sites/{site}/files', [FileController::class, 'store']);
    Route::delete('/sites/{site}/files', [FileController::class, 'clear']);
    Route::delete('/sites/{site}/files/{path}', [FileController::class, 'destroy'])->where('path', '.*');
    Route::get('/visits', VisitController::class);
});
