<?php

namespace App\Http\Requests\Pt;

use Illuminate\Foundation\Http\FormRequest;

class ListPtAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'member_id' => ['sometimes', 'integer', 'min:1'],
            'trainer_id' => ['sometimes', 'integer', 'min:1'],
            'current' => ['sometimes', 'boolean'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'branch_id' => ['prohibited'],
            'actor_id' => ['prohibited'],
            'status' => ['prohibited'],
            'sort' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $current = $this->input('current');
        if (is_string($current)) {
            $normalized = strtolower(trim($current));
            if ($normalized === 'true') {
                $this->merge(['current' => true]);
            } elseif ($normalized === 'false') {
                $this->merge(['current' => false]);
            }
        }
    }
}
