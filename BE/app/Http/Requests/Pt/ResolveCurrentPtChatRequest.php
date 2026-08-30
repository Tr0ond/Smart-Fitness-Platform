<?php

namespace App\Http\Requests\Pt;

use Illuminate\Foundation\Http\FormRequest;

class ResolveCurrentPtChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'assignment_id' => ['sometimes', 'integer', 'min:1'],
            'member_id' => ['prohibited'],
            'trainer_id' => ['prohibited'],
            'pt_id' => ['prohibited'],
            'conversation_id' => ['prohibited'],
            'membership_term_id' => ['prohibited'],
            'usage_id' => ['prohibited'],
            'status' => ['prohibited'],
            'created_at' => ['prohibited'],
        ];
    }
}
