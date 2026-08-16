<?php

use App\Http\Controllers\Api\TestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/**
 * Fixture endpoint for the Cypress suite. It resets filters for *every* user, so it must
 * never exist outside the test environments.
 *
 * Deliberately an allowlist and not "if not production": an unset or misspelled APP_ENV
 * would slip through a denylist, and this route is not one you want to get wrong.
 */
if (App::environment(['local', 'testing'])) {
    Route::post('/test/reset-filters', [TestController::class, 'resetFilters']);
    Route::get('/test/note-count', [TestController::class, 'noteCount']);
}
