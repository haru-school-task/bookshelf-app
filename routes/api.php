<?php

use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// --- 公開API（V1）のルート設計 ---
Route::prefix('v1')->name('api.v1.')->group(function () {

    // ① 認証なし（一覧・詳細） -> ルート名は api.v1.books.index になります
    Route::apiResource('books', BookController::class)->only(['index', 'show']);

    // ② 認証あり（登録・更新・削除） -> ルート名は api.v1.books.store になります
    Route::apiResource('books', BookController::class)
        ->except(['index', 'show'])
        ->middleware('auth:sanctum');

});
