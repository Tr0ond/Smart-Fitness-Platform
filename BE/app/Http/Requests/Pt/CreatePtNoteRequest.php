<?php

namespace App\Http\Requests\Pt;

use Illuminate\Foundation\Http\FormRequest;

class CreatePtNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:10000'],
            'plan_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'session_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'member_id' => ['prohibited'],
            'trainer_id' => ['prohibited'],
            'assignment_id' => ['prohibited'],
            'creator_id' => ['prohibited'],
            'created_at' => ['prohibited'],
        ];
    }
}
