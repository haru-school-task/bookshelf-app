<?php

use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Support\Facades\Route;

// すべてのAPIルートに「api.v1.books.index」などの名前を自動付与するグループ
Route::name('api.v1.')->group(function () {

    // 【基本機能】認証なしでアクセスできる書籍の一覧と詳細ルート
    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

    // 【拡張機能】認証されたユーザーのみがアクセスできる書き込みルート
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/books', [BookController::class, 'store'])->name('books.store');
        Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
        Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
    });

});
