<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateWorkoutTemplateRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'new_code' => ['required', 'string', 'max:40', 'regex:/\A[A-Za-z0-9_-]+\z/'],
            'expected_content_version' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string'],
            'goal' => ['required', 'string', 'max:100'],
            'level' => ['required', 'string', 'max:50'],
            'sessions_per_week' => ['required', 'integer', 'between:1,7'],
            'status' => ['sometimes', 'string', Rule::in(['HOAT_DONG', 'NGUNG_SU_DUNG'])],
            'days' => ['required', 'array', 'min:1', 'max:7'],
            'days.*' => ['required', 'array:order,name,estimated_minutes,exercises'],
            'days.*.order' => ['required', 'integer', 'between:1,7', 'distinct:strict'],
            'days.*.name' => ['required', 'string', 'max:150'],
            'days.*.estimated_minutes' => ['required', 'integer', 'min:1', 'max:65535'],
            'days.*.exercises' => ['required', 'array'],
            'days.*.exercises.*' => ['required', 'array:exercise_id,order,target_sets,min_reps,max_reps,rest_seconds,notes'],
            'days.*.exercises.*.exercise_id' => ['required', 'integer', 'min:1'],
            'days.*.exercises.*.order' => ['required', 'integer', 'min:1', 'max:65535'],
            'days.*.exercises.*.target_sets' => ['required', 'integer', 'min:1', 'max:65535'],
            'days.*.exercises.*.min_reps' => ['required', 'integer', 'min:1', 'max:65535'],
            'days.*.exercises.*.max_reps' => ['required', 'integer', 'min:1', 'max:65535'],
            'days.*.exercises.*.rest_seconds' => ['required', 'integer', 'min:0', 'max:65535'],
            'days.*.exercises.*.notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
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
        foreach (['new_code', 'status'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => strtoupper(trim($this->input($field)))]);
            }
        }
    }
}
