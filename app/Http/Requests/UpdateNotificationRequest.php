<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationRequest extends FormRequest
{
    /**
     * リクエストのユーザー認可判定を行います。
     * 本機能ではPolicy側で厳格な所有者チェックを行うため、ここでは一律でtrueを返します。
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * リクエストに適用するバリデーションルールを定義します。
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'string', 'exists:notifications,id'],
        ];
    }

    /**
     * URLパラメータから渡される通知ID（id）をバリデーション対象に含めるために、
     * データをマージした配列を返却します。
     *
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return array_merge($this->all(), [
            'id' => $this->route('id'),
        ]);
    }
}
