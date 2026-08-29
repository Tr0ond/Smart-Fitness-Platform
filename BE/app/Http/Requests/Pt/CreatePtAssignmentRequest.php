<?php

namespace App\Http\Requests\Pt;

use Illuminate\Foundation\Http\FormRequest;

class CreatePtAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'min:1'],
            'trainer_id' => ['required', 'integer', 'min:1'],
            'start_at' => ['sometimes', 'nullable', 'date'],
            'end_at' => ['sometimes', 'nullable', 'date'],
            'member_profile_id' => ['prohibited'],
            'trainer_profile_id' => ['prohibited'],
            'assignment_id' => ['prohibited'],
            'status' => ['prohibited'],
            'assigned_by_id' => ['prohibited'],
            'membership_term_id' => ['prohibited'],
            'quota' => ['prohibited'],
        ];
    }
}
