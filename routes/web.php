<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*

|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/


Route::get('/', [BookController::class, 'index'])->name('books.index');

Route::get('/ranking', [BookController::class, 'ranking'])->name('ranking.index');

Route::get('/books/isbn/{isbn}', [BookController::class, 'fetchByIsbn']);


Route::middleware(['auth'])->group(function () {

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');

    Route::resource('books', BookController::class)->except(['index', 'show', 'create', 'store']);

    Route::resource('genres', GenreController::class);

    Route::get('/favorites', [BookController::class, 'favorites'])->name('favorites.index');
    Route::post('/books/{book}/favorite', [BookController::class, 'toggleFavorite'])->name('favorites.toggle');

    Route::post('/books/{book}/reviews', [BookController::class, 'storeReview'])->name('reviews.store');
    Route::post('/reviews/{review}/like', [BookController::class, 'toggleLike'])->name('reviews.like');
    Route::get('/reviews/{review}/edit', [BookController::class, 'editReview'])->name('reviews.edit');
    Route::put('/reviews/{review}', [BookController::class, 'updateReview'])->name('reviews.update');
    Route::delete('/reviews/{review}', [BookController::class, 'destroyReview'])->name('reviews.destroy');

    Route::prefix('reading-plans')->name('reading-plans.')->group(function () {
        Route::get('/', [ReadingPlanController::class, 'index'])->name('index');
        Route::get('/create', [ReadingPlanController::class, 'create'])->name('create');
        Route::post('/', [ReadingPlanController::class, 'store'])->name('store');
        Route::get('/{reading_plan}/edit', [ReadingPlanController::class, 'edit'])->name('edit');
        Route::put('/{reading_plan}', [ReadingPlanController::class, 'update'])->name('update');
        Route::delete('/{reading_plan}', [ReadingPlanController::class, 'destroy'])->name('destroy');
        Route::post('/{reading_plan}/complete', [ReadingPlanController::class, 'complete'])->name('complete');
    });

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    });
});


Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
