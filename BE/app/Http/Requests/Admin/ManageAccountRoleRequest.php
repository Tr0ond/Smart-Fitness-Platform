<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ManageAccountRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'account_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'nguoi_dung_id' => ['prohibited'],
            'role' => ['prohibited'],
            'role_id' => ['prohibited'],
            'vai_tro_id' => ['prohibited'],
            'granted_by_id' => ['prohibited'],
            'nguoi_cap_id' => ['prohibited'],
            'granted_at' => ['prohibited'],
            'cap_luc' => ['prohibited'],
            'revoked_at' => ['prohibited'],
            'thu_hoi_luc' => ['prohibited'],
        ];
    }
}
