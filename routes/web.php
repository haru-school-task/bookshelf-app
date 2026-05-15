<?php

use App\Http\Controllers\BookController; // 後でコントローラーを作る時に使います
use App\Http\Controllers\GenreController; // ★この1行を追加！
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

// 1. トップページ（玄関）
Route::get('/', [BookController::class, 'index'])->name('books.index');

// 2. 書籍関連ルート（【完全確定版】通常のリソースを正しい順序で配置）
// ✅ ①「create」「store」を含むログイン必須ルートを【先】に書く
Route::resource('books', BookController::class)->except(['index', 'show'])->middleware('auth');

// ✅ ②「index」「show」を含む一般公開ルートを【後】に書く（※必ず apiResource ではなく resource に！）
Route::resource('books', BookController::class)->only(['index', 'show']);

// ★マイ読書レポートは書籍リソースの「下」へ配置
Route::get('/reports', [BookController::class, 'report'])->name('reports.index')->middleware('auth');


// routes/web.php の3番をこれに書き換え

// 3. ジャンル関連ルート（【要件完全一致版】すべてをログイン必須の盾でガードします）
// 2行に分かれていたものを消して、以下の「1行」へ綺麗にドッキングさせてください
Route::resource('genres', GenreController::class)->middleware('auth');

// 4. お守り・その他アクション（本物ルート）
Route::get('/ranking', [BookController::class, 'ranking'])->name('ranking.index');
Route::get('/favorites', [BookController::class, 'favorites'])->name('favorites.index')->middleware('auth');

Route::post('/books/{book}/favorite', [BookController::class, 'toggleFavorite'])->name('favorites.toggle')->middleware('auth');
Route::post('/books/{book}/reviews', [BookController::class, 'storeReview'])->name('reviews.store')->middleware('auth');
Route::post('/reviews/{review}/like', [BookController::class, 'toggleLike'])->name('reviews.like')->middleware('auth');

Route::get('/reviews/{review}/edit', [BookController::class, 'editReview'])->name('reviews.edit')->middleware('auth');
Route::put('/reviews/{review}', [BookController::class, 'updateReview'])->name('reviews.update')->middleware('auth');
Route::delete('/reviews/{review}', [BookController::class, 'destroyReview'])->name('reviews.destroy')->middleware('auth');

// 4. ログイン関連（画面表示 ＆ 強制ログイン）
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    auth()->loginUsingId(1); // 山田太郎としてログイン
    return redirect()->route('books.index');
})->name('login.store');

// 5. 会員登録関連（画面表示 ＆ 強制ログイン）
Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/register', function () {
    auth()->loginUsingId(1); // 登録ボタン押下時も自動で山田太郎としてログイン
    return redirect()->route('books.index');
})->name('register.store');

// 6. ログアウト処理
Route::post('/logout', function () {
    auth()->logout();
    return redirect()->route('books.index');
})->name('logout');