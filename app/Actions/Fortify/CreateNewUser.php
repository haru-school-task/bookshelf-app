<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * 新しく登録されたユーザーのバリデーションを行い、データベースへ保存して返却します。
     *
     * 【型宣言・PHPDoc完全対応】
     *
     * @param  array<string, string>  $input  入力データ
     * @return User 作成されたユーザーインスタンス
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z\p{Hiragana}\p{Katakana}\p{Han}　 ]+$/u',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ], [

            'name.required' => 'お名前は必須項目です。',
            'name.string' => 'お名前は正しい文字形式で入力してください。',
            'name.max' => 'お名前は50文字以内で入力してください。',
            'name.regex' => 'お名前の形式が正しくありません。',

            'email.required' => 'メールアドレスは必須項目です。',
            'email.string' => 'メールアドレスは正しい文字形式で入力してください。',
            'email.email' => '正しいメールアドレスの形式（@を含む形式）で入力してください。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'email.unique' => 'このメールアドレスは既に登録されています。',

            'password.required' => 'パスワードは必須項目です。',
            'password.confirmed' => 'パスワード（確認用）と一致しません。',

            'password' => 'パスワードは8文字以上で入力してください。',
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
