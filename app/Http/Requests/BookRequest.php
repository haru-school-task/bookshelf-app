<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'genre_ids' => ['required', 'array'], // ジャンル選択は必須
            'genre_ids.*' => ['exists:genres,id'], // 選ばれたIDがDBに存在するかチェック
            'isbn' => ['nullable', 'string', 'unique:books,isbn,' . $this->book?->id], // 自分以外の重複を禁止
            'description' => ['nullable', 'string', 'max:400'], // ★指示書の「400文字制限」
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'], // 画像バリデーション
        ];
    }
}
