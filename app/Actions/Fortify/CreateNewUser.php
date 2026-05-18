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
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // 💡 第3引数（配列の3つ目）に自作の日本語メッセージをガチッとはめ込みます [INDEX1]
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ], [
            // ★【要件完全一致】日本語バリデーションメッセージの設計 [INDEX1]
            'name.required'     => 'お名前は必須項目です。',
            'name.string'       => 'お名前は正しい文字形式で入力してください。',
            'name.max'          => 'お名前は255文字以内で入力してください。',
            
            'email.required'    => 'メールアドレスは必須項目です。',
            'email.string'      => 'メールアドレスは正しい文字形式で入力してください。',
            'email.email'       => '正しいメールアドレスの形式（@を含む形式）で入力してください。',
            'email.max'         => 'メールアドレスは255文字以内で入力してください。',
            'email.unique'      => 'このメールアドレスは既に登録されています。',
            
            'password.required' => 'パスワードは必須項目です。',
            'password.confirmed'=> 'パスワード（確認用）と一致しません。',
            // ※PasswordValidationRulesを使用している場合、文字数制限などはそちらに準拠します
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }
}

