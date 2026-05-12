<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    public function index()
    {
        // 全てのジャンルを取得
        $genres = Genre::all();
        // ジャンル管理の一覧画面（genres.index）を表示
        return view('genres.index', compact('genres'));
    }
}
