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

// 2. 書籍関連ルート（ガード付き）
Route::resource('books', BookController::class)->middleware('auth');

// 3. お守り（Blade内のエラー防止用）
Route::get('/ranking', fn() => 'ranking')->name('ranking.index');
Route::get('/favorites', fn() => 'favorites')->name('favorites.index');
Route::get('/genres', fn() => 'genres')->name('genres.index');
// ★ これを追記！お気に入りボタンの叫びを静めるお守りです
Route::post('/books/{book}/favorite', fn() => 'favorite')->name('favorites.toggle');
// ★ これを追記！レビュー送信ボタンの叫びを静めるお守りです
Route::post('/books/{book}/reviews', fn() => 'review')->name('reviews.store');
// （既存のお守りたちの下に...）
Route::post('/reviews/{review}/like', fn() => 'like')->name('reviews.like');

// 4. ログイン関連（画面表示 ＆ 強制ログイン）
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    auth()->loginUsingId(1); // 山田太郎としてログイン
    return redirect()->route('books.index');
})->name('login.store');

// 5. 【新規！】会員登録関連（画面表示 ＆ 登録ボタン押下時の強制ログイン）
Route::get('/register', function () {
    return view('auth.register'); // 本物の会員登録画面（お皿）を返す！
})->name('register');

Route::post('/register', function () {
    // 開発を爆速にする魔法：登録ボタンを押したら自動で山田太郎（ID: 1）としてログイン
    auth()->loginUsingId(1);
    return redirect()->route('books.index');
})->name('register.store');

// 6. ログアウト処理（ボタンを押したらサインアウトしてトップへ戻す）
Route::post('/logout', function () {
    auth()->logout(); // ログイン状態を解除
    return redirect()->route('books.index'); // トップ画面へ戻る
})->name('logout');

