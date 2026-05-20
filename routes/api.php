<?php

use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Support\Facades\Route;

// ⭕ すべてのAPIルートに「api.v1.books.index」などの名前を自動付与するグループ
Route::name('api.v1.')->group(function () {

    // 【基本機能】誰でもアクセスできる読み取りルート（認証なし）
    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

    // 【応用機能】★Sanctumの暗号トークンが無いと絶対に叩けない鉄壁の部屋 [INDEX3]
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/books', [BookController::class, 'store'])->name('books.store');
        Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
        Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
    });

});
