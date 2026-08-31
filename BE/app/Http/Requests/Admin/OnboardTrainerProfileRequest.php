<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OnboardTrainerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'introduction' => ['nullable', 'string', 'max:16383'],
            'specialties' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in(['HOAT_DONG', 'NGUNG_NHAN_PHAN_CONG'])],
            'name' => ['prohibited'],
            'email' => ['prohibited'],
            'phone' => ['prohibited'],
            'trainer_code' => ['prohibited'],
            'role' => ['prohibited'],
            'roles' => ['prohibited'],
            'account_id' => ['prohibited'],
            'nguoi_dung_id' => ['prohibited'],
            'password' => ['prohibited'],
            'mat_khau_bam' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('status'))) {
            $this->merge(['status' => strtoupper(trim($this->input('status')))]);
        }
    }
}
