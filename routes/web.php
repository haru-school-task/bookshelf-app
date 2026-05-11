<?php

use App\Http\Controllers\BookController; // 後でコントローラーを作る時に使います
use Illuminate\Support\Facades\Route;
use App\Models\Book;
use App\Models\Genre;

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

// 1. トップページを books.index に紐付ける（これが一番上にあると安心です）
Route::get('/', [BookController::class, 'index'])->name('books.index');

// 2. その他の書籍関連ルートを一括定義
Route::resource('books', BookController::class);

// --- お守り（これらがないとBladeがエラーになります） ---
Route::get('/ranking', fn() => 'ranking')->name('ranking.index');
Route::get('/favorites', fn() => 'favorites')->name('favorites.index');
Route::get('/genres', fn() => 'genres')->name('genres.index');
Route::get('/login', fn() => 'login')->name('login');
Route::get('/register', fn() => 'register')->name('register'); // ← これを追記！

