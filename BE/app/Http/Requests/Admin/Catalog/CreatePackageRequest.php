<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'regex:/\A[A-Za-z0-9_-]+\z/'],
            'name' => ['required', 'string', 'max:150'],
            'price' => ['required', 'integer', 'min:1', 'max:999999999999999'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:65535'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(['DANG_BAN', 'NGUNG_BAN'])],
            'benefits' => ['required', 'array'],
            'benefits.gym_access' => ['required', 'boolean'],
            'benefits.fitness_assistant' => ['required', 'boolean'],
            'benefits.fitness_assistant_limit' => ['present', 'nullable', 'integer', 'min:0', 'max:4294967295'],
            'benefits.trainer_chat' => ['required', 'boolean'],
            'benefits.direct_trainer_sessions' => ['required', 'integer', 'min:0', 'max:65535'],
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
        foreach (['code', 'status'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => strtoupper(trim($this->input($field)))]);
            }
        }
    }
}
