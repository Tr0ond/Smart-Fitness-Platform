<?php

namespace App\Http\Requests\Pt;

use Illuminate\Foundation\Http\FormRequest;

class ReassignPtAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'trainer_id' => ['required', 'integer', 'min:1'],
            'start_at' => ['sometimes', 'nullable', 'date'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
            'member_id' => ['prohibited'],
            'assignment_id' => ['prohibited'],
            'status' => ['prohibited'],
            'end_at' => ['prohibited'],
            'membership_term_id' => ['prohibited'],
            'quota' => ['prohibited'],
        ];
    }
}
