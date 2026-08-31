<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAccountsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:150'],
            'status' => ['sometimes', 'string', Rule::in(['HOAT_DONG', 'BI_KHOA', 'NGUNG_HOAT_DONG'])],
            'role' => ['sometimes', 'string', Rule::in(['MEMBER', 'PT', 'RECEPTIONIST', 'ADMIN'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['status', 'role'] as $truong) {
            if (is_string($this->input($truong))) {
                $this->merge([$truong => strtoupper(trim($this->input($truong)))]);
            }
        }
    }
}
