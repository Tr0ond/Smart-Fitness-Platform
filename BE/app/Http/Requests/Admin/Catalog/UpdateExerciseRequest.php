<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:200'],
            'difficulty' => ['sometimes', 'required', 'string', 'max:30'],
            'instructions' => ['sometimes', 'required', 'string'],
            'image_path' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'video_path' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['HOAT_DONG', 'NGUNG_SU_DUNG'])],
            'equipment_ids' => ['sometimes', 'array'],
            'equipment_ids.*' => ['integer', 'distinct:strict', 'min:1'],
            'muscle_groups' => ['sometimes', 'array'],
            'muscle_groups.*' => ['required', 'array:id,role'],
            'muscle_groups.*.id' => ['required', 'integer', 'distinct:strict', 'min:1'],
            'muscle_groups.*.role' => ['required', 'string', Rule::in(['CHINH', 'PHU'])],
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
