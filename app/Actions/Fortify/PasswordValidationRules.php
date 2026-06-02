<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
     /**
     * 会員登録時等に適用するパスワード検証ルールを定義します。
     * 指定の最小文字数（8文字）を引数に正しく渡してインスタンス化を行います。
     *
     * @return array<int, mixed>
     */
    protected function passwordRules(): array
    {
        $passwordRule = new Password(8);

        return [
            'required',
            'string',
            $passwordRule,
            'confirmed',
        ];
    }
}