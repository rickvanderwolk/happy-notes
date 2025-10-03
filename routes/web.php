<?php

use App\Http\Controllers\FilterController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProfileExportController;
use App\Http\Middleware\StoreOriginalRoute;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verificatiemail verzonden!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

$defaultAppMiddlewares = ['auth:sanctum', config('jetstream.auth_session')];
if (config('app.force_email_verification')) {
    $defaultAppMiddlewares[] = 'verified';
}
$defaultAppMiddlewares[] = StoreOriginalRoute::class;

Route::middleware($defaultAppMiddlewares)->group(function () {
    Route::get('/menu', function () {
        return view('menu');
    })->name('menu.show');

    Route::get('/shortcuts', function () {
        return view('shortcuts');
    })->name('shortcuts.show');

    Route::get('/', function () { return redirect(route('notes.show')); })->name('dashboard');
    Route::get('/notes', [NoteController::class, 'index'])->name('notes.show');
    Route::post('/notes', [NoteController::class, 'store'])->name('note.store');
    Route::get('/notes/new', [NoteController::class, 'create'])->name('note.create');
    Route::get('/notes/{note}', [NoteController::class, 'show'])->name('note.show');
    Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('note.destroy');
    Route::get('/notes/{note}/title', [NoteController::class, 'formTitle'])->name('note.title.show');
    Route::post('/notes/{note}/title', [NoteController::class, 'storeTitle'])->name('note.title.store');
    Route::get('/notes/{note}/emojis', [NoteController::class, 'formEmojis'])->name('note.emojis.show');
    Route::post('/notes/{note}/emojis', [NoteController::class, 'storeEmojis'])->name('note.emojis.store');
    Route::post('/notes/{note}/body', [NoteController::class, 'storeBody'])->name('note.body.store');

    Route::get('/filter', [FilterController::class, 'index'])->name('filter.show');
    Route::get('/filter/exclude', [FilterController::class, 'index'])->name('filter.exclude.show');
    Route::get('/filter/search', [FilterController::class, 'search'])->name('filter.search.show');
    Route::post('/filter/clear', [FilterController::class, 'clear'])->name('filter.clear');
    Route::post('/search/clear', [FilterController::class, 'clearSearch'])->name('search.clear');

    Route::get('/user/export', [ProfileExportController::class, 'export'])->name('user.export.notes.create');
});
