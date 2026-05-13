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

// --- 公開API（V1）：応用機能（Sanctumによるトークン認証を後付け） ---
Route::prefix('v1')->name('api.v1.')->group(function () {

    // ① 基本機能：認証なしで誰でもアクセスできるルート（一覧・詳細）
    Route::apiResource('books', BookController::class)->only(['index', 'show']);

    // ② 応用機能：★ここでSanctumトークン認証を後付け（登録・更新・削除を鉄壁にガード）
    Route::apiResource('books', BookController::class)
        ->except(['index', 'show'])
        ->middleware('auth:sanctum');

});
