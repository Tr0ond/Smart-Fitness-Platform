<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:64', 'regex:/\A[0-9a-f]{64}\z/'],
            'password' => [
                'required',
                'string',
                'max:4096',
                'confirmed',
                Password::min(12)->mixedCase()->letters()->numbers()->symbols(),
            ],
            'user_id' => ['prohibited'],
            'nguoi_dung_id' => ['prohibited'],
            'email' => ['prohibited'],
            'status' => ['prohibited'],
            'trang_thai' => ['prohibited'],
        ];
    }
}
