<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReplaceEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'equipment_ids' => ['present', 'array'],
            'equipment_ids.*' => [
                'required',
                'integer',
                'distinct:strict',
                Rule::exists('dung_cu', 'id'),
            ],
        ];
    }
}
