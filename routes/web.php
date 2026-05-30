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

// =========================================================================
// 🔓 1. ゲスト（未ログイン）でもアクセス可能なルート群
// =========================================================================

// トップページ（書籍一覧）
Route::get('/', [BookController::class, 'index'])->name('books.index');

// 評価ランキング画面
Route::get('/ranking', [BookController::class, 'ranking'])->name('ranking.index');

// ISBNによる書籍検索公開API（非同期通信用：衝突を防ぐためグループ外の最上部に配置）
Route::get('/books/isbn/{isbn}', [BookController::class, 'fetchByIsbn']);


// =========================================================================
// 🔒 2. 認証（ログイン）必須のルート群（Route Group で一括管理）
// =========================================================================
Route::middleware(['auth'])->group(function () {

    // 📊 マイ読書レポート
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // 💡【404完全解消・超重要】書籍登録（create）と保存（store）
    // ゲスト用の show（books/{book}）よりも物理的に「上」で宣言することで、URLの衝突を100%回避します
    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');

    // 📚 その他の書籍管理（編集・更新・削除機能のみを定義）
    Route::resource('books', BookController::class)->except(['index', 'show', 'create', 'store']);

    // 🏷️ ジャンル管理
    Route::resource('genres', GenreController::class);

    // ❤️ お気に入り関連
    Route::get('/favorites', [BookController::class, 'favorites'])->name('favorites.index');
    Route::post('/books/{book}/favorite', [BookController::class, 'toggleFavorite'])->name('favorites.toggle');

    // ✍️ レビュー関連（新規投稿・いいね・編集・更新・削除）
    Route::post('/books/{book}/reviews', [BookController::class, 'storeReview'])->name('reviews.store');
    Route::post('/reviews/{review}/like', [BookController::class, 'toggleLike'])->name('reviews.like');
    Route::get('/reviews/{review}/edit', [BookController::class, 'editReview'])->name('reviews.edit');
    Route::put('/reviews/{review}', [BookController::class, 'updateReview'])->name('reviews.update');
    Route::delete('/reviews/{review}', [BookController::class, 'destroyReview'])->name('reviews.destroy');

    // 📅 読書計画（ReadingPlan）関連ルート
    Route::prefix('reading-plans')->name('reading-plans.')->group(function () {
        Route::get('/', [ReadingPlanController::class, 'index'])->name('index');
        Route::get('/create', [ReadingPlanController::class, 'create'])->name('create');
        Route::post('/', [ReadingPlanController::class, 'store'])->name('store');
        Route::get('/{reading_plan}/edit', [ReadingPlanController::class, 'edit'])->name('edit');
        Route::put('/{reading_plan}', [ReadingPlanController::class, 'update'])->name('update');
        Route::delete('/{reading_plan}', [ReadingPlanController::class, 'destroy'])->name('destroy');
        Route::post('/{reading_plan}/complete', [ReadingPlanController::class, 'complete'])->name('complete');
    });

    // 🔔 通知（Notification）関連ルート
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    });
});


// =========================================================================
// 🔓 3. ゲスト用書籍個別ルート（💡404回避のため一番最後に配置）
// =========================================================================
// これを最下部に移動したことで、/books/create が詳細画面（books/{book}）として誤認されるのを完全に防ぎます
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
