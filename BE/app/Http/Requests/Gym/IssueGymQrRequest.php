<?php

namespace App\Http\Requests\Gym;

use Illuminate\Foundation\Http\FormRequest;

class IssueGymQrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'member_id' => ['prohibited'],
            'hoi_vien_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'nguoi_dung_id' => ['prohibited'],
            'branch_id' => ['prohibited'],
            'chi_nhanh_id' => ['prohibited'],
            'ttl_seconds' => ['prohibited'],
            'expires_at' => ['prohibited'],
            'issued_at' => ['prohibited'],
        ];
    }
}
