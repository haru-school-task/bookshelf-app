<?php

use App\Http\Controllers\BookController; // 後でコントローラーを作る時に使います
use App\Http\Controllers\GenreController; // ★この1行を追加！
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReadingPlanController;
use Illuminate\Support\Facades\Route;

/*

|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------

|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will

| be assigned to the "web" middleware group. Make something great!
|
*/

// 1. トップページ
Route::get('/', [BookController::class, 'index'])->name('books.index');

// 2. 書籍関連ルート
Route::resource('books', BookController::class)->except(['index', 'show'])->middleware('auth');

Route::resource('books', BookController::class)->only(['index', 'show']);

Route::get('/reports', [BookController::class, 'report'])->name('reports.index')->middleware('auth');

// 3. ジャンル関連ルート
Route::resource('genres', GenreController::class)->middleware('auth');

// 4. その他アクション　（ランキング、マイページ、レビュー関連など）
Route::get('/ranking', [BookController::class, 'ranking'])->name('ranking.index');
Route::get('/favorites', [BookController::class, 'favorites'])->name('favorites.index')->middleware('auth');

Route::post('/books/{book}/favorite', [BookController::class, 'toggleFavorite'])->name('favorites.toggle')->middleware('auth');
Route::post('/books/{book}/reviews', [BookController::class, 'storeReview'])->name('reviews.store')->middleware('auth');
Route::post('/reviews/{review}/like', [BookController::class, 'toggleLike'])->name('reviews.like')->middleware('auth');

Route::get('/reviews/{review}/edit', [BookController::class, 'editReview'])->name('reviews.edit')->middleware('auth');
Route::put('/reviews/{review}', [BookController::class, 'updateReview'])->name('reviews.update')->middleware('auth');
Route::delete('/reviews/{review}', [BookController::class, 'destroyReview'])->name('reviews.destroy')->middleware('auth');

Route::get('/books/isbn/{isbn}', [BookController::class, 'fetchByIsbn']);

// =========================================================================
// 【新応用要件】読書計画（ReadingPlan）関連ルート（認証必須）
// =========================================================================
Route::middleware('auth')->group(function () {

    Route::get('/reading-plans', [ReadingPlanController::class, 'index'])
        ->name('reading-plans.index');

    // PG16: 読書計画作成（書籍プルダウン・期日入力フォーム）
    Route::get('/reading-plans/create', [ReadingPlanController::class, 'create'])
        ->name('reading-plans.create');
    Route::post('/reading-plans', [ReadingPlanController::class, 'store'])
        ->name('reading-plans.store');

    // PG17: 読書計画編集（作成者のみ閲覧・期日変更フォーム）
    Route::get('/reading-plans/{reading_plan}/edit', [ReadingPlanController::class, 'edit'])
        ->name('reading-plans.edit');
    Route::put('/reading-plans/{reading_plan}', [ReadingPlanController::class, 'update'])
        ->name('reading-plans.update');
    Route::delete('/reading-plans/{reading_plan}', [ReadingPlanController::class, 'destroy'])
        ->name('reading-plans.destroy');

    Route::post('/reading-plans/{reading_plan}/complete', [ReadingPlanController::class, 'complete'])
        ->name('reading-plans.complete');
});

// =========================================================================
// 【新応用要件】通知（Notification）関連ルート（認証必須）
// =========================================================================
Route::middleware('auth')->group(function () {
    // PG18: 通知一覧の表示、および各通知の既読化アクション（DatabaseChannel連動）
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');
});
