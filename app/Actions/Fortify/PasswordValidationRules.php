<?php

namespace App\Actions\Fortify;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
     /**
     * 会員登録時等に適用するパスワード検証ルールを定義します。
     * スクール指定の最小文字数（8文字）を引数に正しく渡してインスタンス化を行います。
     *
     * @return array<int, mixed>
     */
    protected function passwordRules(): array
    {
        // 💡 修正ポイント：new Password のカッコの中に「8」を確実に渡します
        $passwordRule = new Password(8);

        return [
            'required',
            'string',
            $passwordRule,
            'confirmed',
        ];
    }
}