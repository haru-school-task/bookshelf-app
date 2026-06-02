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
            'title' => ['required', 'string', 'max:100'],
            'author' => ['required', 'string', 'max:50'],
            'genre_ids' => ['required', 'array'],
            'genre_ids.*' => ['exists:genres,id'],
            'isbn' => ['nullable', 'string', 'max:13', 'unique:books,isbn,' . (is_object($this->book) ? $this->book->id : $this->book)],
            'description' => ['nullable', 'string', 'max:400'],
            'title_kana' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:2000'],
            'display_image_url' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => '書籍のタイトルは必須項目です。',
            'title.string' => 'タイトルは正しい文字形式で入力してください。',
            'title.max' => 'タイトルは100文字以内で入力してください。',

            'author.required' => '著者名は必須項目です。',
            'author.string' => '著者名は正しい文字形式で入力してください。',
            'author.max' => '著者名は50文字以内で入力してください。',

            'genre_ids.required' => 'ジャンルは少なくとも1つ以上選択してください。',
            'genre_ids.array' => 'ジャンルの選択形式が不正です。',
            'genre_ids.*.exists' => '選択されたジャンルは存在しません。',

            'isbn.string' => 'ISBNは正しい文字形式で入力してください。',
            'isbn.unique' => 'このISBNは既に他の書籍で登録されています。',
            'isbn.max' => 'ISBNは13文字以内で入力してください。',

            'description.string' => '説明文は正しい文字形式で入力してください。',
            'description.max' => '説明文は400文字以内で入力してください。',

            'image_url.max' => '画像URLは2000文字以内で入力してください。',
            'display_image_url.max' => '画像URLは2000文字以内で入力してください。',

            'image.image' => '指定されたファイルが画像ではありません。',
            'image.mimes' => '画像の種類はjpeg、png、jpgのいずれかを選択してください。',
            'image.max' => '画像のファイルサイズは2MB以内でアップロードしてください。',
        ];
    }
}
