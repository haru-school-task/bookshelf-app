<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'genre_ids' => ['required', 'array'], // ジャンル選択は必須
            'genre_ids.*' => ['exists:genres,id'], // 選ばれたIDがDBに存在するかチェック
            'isbn' => ['nullable', 'string', 'unique:books,isbn,'.$this->book?->id], // 自分以外の重複を禁止
            'description' => ['nullable', 'string', 'max:400'], // 解説文は400文字以内

            'title_kana' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'string'],
        ];
    }

    /**
     * 指示書要件：日本語のバリデーションメッセージを自分で設計して定義
     */
    public function messages(): array
    {
        return [
            'title.required' => '書籍のタイトルは必須項目です。',
            'title.string' => 'タイトルは正しい文字形式で入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',

            'author.required' => '著者名は必須項目です。',
            'author.string' => '著者名は正しい文字形式で入力してください。',
            'author.max' => '著者名は255文字以内で入力してください。',

            'genre_ids.required' => 'ジャンルは少なくとも1つ以上選択してください。',
            'genre_ids.array' => 'ジャンルの選択形式が不正です。',
            'genre_ids.*.exists' => '選択されたジャンルは存在しません。',

            'isbn.string' => 'ISBNは正しい文字形式で入力してください。',
            'isbn.unique' => 'このISBNは既に他の書籍で登録されています。',

            'description.string' => '解説文は正しい文字形式で入力してください。',
            'description.max' => '解説文は400文字以内で入力してください。',

            'image.image' => '指定されたファイルが画像ではありません。',
            'image.mimes' => '画像の種類はjpeg、png、jpgのいずれかを選択してください。',
            'image.max' => '画像のファイルサイズは2MB以内でアップロードしてください。',
        ];
    }
}
