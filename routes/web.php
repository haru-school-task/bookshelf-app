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

// 書籍一覧（トップページ）
Route::get('/', function () {
    // DBから本とジャンルを「本物」のデータとして取得する
    $books = Book::paginate(10);
    $genres = Genre::all();

    return view('books.index', [
        'books' => $books,
        'genres' => $genres
    ]);
})->name('books.index');

// 書籍詳細画面（仮）
Route::get('/books/{book}', function ($id) {
    return "書籍詳細画面（ID: {$id}）の準備中です！";
})->name('books.show');

// 書籍登録（仮）
Route::get('/books/create', function () {
    return '書籍登録画面（準備中）';
})->name('books.create');

// ランキング（仮）
Route::get('/ranking', function () {
    return 'ランキング画面（準備中）';
})->name('ranking.index');

// お気に入り一覧（仮）
Route::get('/favorites', function () {
    return 'お気に入り一覧（準備中）';
})->name('favorites.index');

// ジャンル一覧（仮） ← 今回のターゲットはこれ！
Route::get('/genres', function () {
    return 'ジャンル一覧画面（準備中）';
})->name('genres.index');

// ログイン（仮）
Route::get('/login', function () {
    return 'ログイン画面（準備中）';
})->name('login');

// 会員登録（仮）
Route::get('/register', function () {
    return '会員登録画面（準備中）';
})->name('register');