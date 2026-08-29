<?php

namespace App\Http\Requests\Pt;

use Illuminate\Foundation\Http\FormRequest;

class EndPtAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
            'end_at' => ['prohibited'],
            'status' => ['prohibited'],
            'member_id' => ['prohibited'],
            'trainer_id' => ['prohibited'],
            'assignment_id' => ['prohibited'],
            'membership_term_id' => ['prohibited'],
            'quota' => ['prohibited'],
        ];
    }
}
