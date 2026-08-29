<?php

namespace App\Http\Requests\Gym;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'qr_token' => ['required', 'string', 'size:64', 'regex:/\A[0-9a-f]{64}\z/'],
            'member_id' => ['prohibited'],
            'hoi_vien_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'nguoi_dung_id' => ['prohibited'],
            'branch_id' => ['prohibited'],
            'chi_nhanh_id' => ['prohibited'],
        ];
    }
}
