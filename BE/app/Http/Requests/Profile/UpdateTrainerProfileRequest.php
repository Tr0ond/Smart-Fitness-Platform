<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'introduction' => ['sometimes', 'nullable', 'string', 'max:16383'],
            'specialties' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
