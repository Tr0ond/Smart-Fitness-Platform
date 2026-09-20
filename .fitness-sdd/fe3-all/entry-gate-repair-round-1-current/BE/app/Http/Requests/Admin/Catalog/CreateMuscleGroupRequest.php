<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateMuscleGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:40', 'regex:/\A[A-Za-z0-9_-]+\z/'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'string', Rule::in(['HOAT_DONG', 'NGUNG_SU_DUNG'])],
            'id' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'ngay_tao' => ['prohibited'],
            'ngay_cap_nhat' => ['prohibited'],
            'branch_id' => ['prohibited'],
            'chi_nhanh_id' => ['prohibited'],
            'actor_id' => ['prohibited'],
            'nguoi_thuc_hien_id' => ['prohibited'],
            'created_by_id' => ['prohibited'],
            'nguoi_tao_id' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $values = [];
        foreach (['code', 'status'] as $field) {
            if (is_string($this->input($field))) {
                $values[$field] = strtoupper(trim($this->input($field)));
            }
        }
        if ($values !== []) {
            $this->merge($values);
        }
    }
}
