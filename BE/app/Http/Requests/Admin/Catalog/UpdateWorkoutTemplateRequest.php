<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkoutTemplateRequest extends FormRequest
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
            'description' => ['sometimes', 'nullable', 'string'],
            'goal' => ['sometimes', 'required', 'string', 'max:100'],
            'level' => ['sometimes', 'required', 'string', 'max:50'],
            'sessions_per_week' => ['sometimes', 'required', 'integer', 'between:1,7'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['HOAT_DONG', 'NGUNG_SU_DUNG'])],
            // Cây ngày/bài chỉ thay qua endpoint revisions để không hard-delete history.
            'days' => ['prohibited'],
            'code' => ['prohibited'],
            'content_version' => ['prohibited'],
            'phien_ban_noi_dung' => ['prohibited'],
            'created_by_id' => ['prohibited'],
            'nguoi_tao_id' => ['prohibited'],
            'id' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('status'))) {
            $this->merge(['status' => strtoupper(trim($this->input('status')))]);
        }
    }
}
