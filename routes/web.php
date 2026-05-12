<?php

use App\Http\Controllers\BookController; // 後でコントローラーを作る時に使います
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

// 2. 書籍関連ルート（ガードを最適化）
// 一覧（index）と詳細（show）は誰でも見られるオープンな道にする
Route::resource('books', BookController::class)->only(['index', 'show']);

// 登録・編集・更新・削除（create, store, edit, update, destroy）はログイン必須にする
Route::resource('books', BookController::class)->except(['index', 'show'])->middleware('auth');

// 3. お守り（Blade内のエラー防止用）
Route::get('/ranking', fn() => 'ranking')->name('ranking.index');
Route::get('/favorites', fn() => 'favorites')->name('favorites.index');
Route::get('/genres', fn() => 'genres')->name('genres.index');
Route::post('/books/{book}/favorite', fn() => 'favorite')->name('favorites.toggle');
Route::post('/books/{book}/reviews', fn() => 'review')->name('reviews.store');
Route::post('/reviews/{review}/like', fn() => 'like')->name('reviews.like');

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