<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'days' => ['present', 'array', 'max:7'],
            'days.*' => ['required', 'integer', 'between:2,8', 'distinct:strict'],
        ];
    }
}
