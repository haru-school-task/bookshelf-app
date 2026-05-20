<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
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
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:400'], // 400文字制限
        ];
    }

    /**
     * ★【新規追記！】指示書要件：日本語のバリデーションメッセージを自分で設計して定義
     */
    public function messages(): array
    {
        return [
            'rating.required' => '評価値（星の数）の選択は必須項目です。',
            'rating.integer' => '評価値は正しい数値形式で選択してください。',
            'rating.between' => '評価値は1から5の範囲内で選択してください。',

            'comment.required' => 'コメントは必須項目です。',
            'comment.string' => 'コメントは正しい文字形式で入力してください。',
            'comment.max' => 'コメントは400文字以内で入力してください。',
        ];
    }
}
