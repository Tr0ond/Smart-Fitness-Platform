<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'birth_date' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'gender' => ['sometimes', 'nullable', 'string', 'max:30'],
            'training_goal' => ['sometimes', 'nullable', 'string', 'max:100'],
            'training_experience' => ['sometimes', 'nullable', 'string', 'max:50'],
            'desired_training_days' => ['sometimes', 'nullable', 'integer', 'between:1,7'],
            'session_duration_minutes' => ['sometimes', 'nullable', 'integer', 'between:1,65535'],
        ];
    }
}
