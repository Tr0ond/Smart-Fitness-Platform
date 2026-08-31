<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'price' => ['sometimes', 'required', 'integer', 'min:1', 'max:999999999999999'],
            'duration_days' => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['DANG_BAN', 'NGUNG_BAN'])],
            'code' => ['prohibited'],
            'benefits' => ['prohibited'],
            'branch_id' => ['prohibited'],
            'chi_nhanh_id' => ['prohibited'],
            'created_by_id' => ['prohibited'],
            'nguoi_tao_id' => ['prohibited'],
            'configuration_version' => ['prohibited'],
            'phien_ban_cau_hinh' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('status'))) {
            $this->merge(['status' => strtoupper(trim($this->input('status')))]);
        }
    }
}
