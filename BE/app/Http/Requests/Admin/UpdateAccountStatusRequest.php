<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['HOAT_DONG', 'BI_KHOA', 'NGUNG_HOAT_DONG'])],
            'account_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'nguoi_dung_id' => ['prohibited'],
            'roles' => ['prohibited'],
            'role' => ['prohibited'],
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
