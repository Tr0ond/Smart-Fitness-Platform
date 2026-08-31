<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return $this->exerciseRules(true);
    }

    /** @return array<string, array<int, mixed>> */
    protected function exerciseRules(bool $creating): array
    {
        return [
            'code' => $creating
                ? ['required', 'string', 'max:40', 'regex:/\A[A-Za-z0-9_-]+\z/']
                : ['prohibited'],
            'name' => ['required', 'string', 'max:200'],
            'difficulty' => ['required', 'string', 'max:30'],
            'instructions' => ['required', 'string'],
            'image_path' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'video_path' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'string', Rule::in(['HOAT_DONG', 'NGUNG_SU_DUNG'])],
            'equipment_ids' => ['present', 'array'],
            'equipment_ids.*' => ['integer', 'distinct:strict', 'min:1'],
            'muscle_groups' => ['present', 'array'],
            'muscle_groups.*' => ['required', 'array:id,role'],
            'muscle_groups.*.id' => ['required', 'integer', 'distinct:strict', 'min:1'],
            'muscle_groups.*.role' => ['required', 'string', Rule::in(['CHINH', 'PHU'])],
            'content_version' => ['prohibited'],
            'phien_ban_noi_dung' => ['prohibited'],
            'created_by_id' => ['prohibited'],
            'nguoi_tao_id' => ['prohibited'],
            'id' => ['prohibited'],
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
