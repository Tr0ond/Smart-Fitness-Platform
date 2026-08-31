<?php

namespace App\Http\Requests\Progress;

use Illuminate\Foundation\Http\FormRequest;

class ListBodyMeasurementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'limit' => ['sometimes', 'integer', 'between:1,100'],
            'before_measured_at' => ['sometimes', 'required_with:before_id', 'date'],
            'before_id' => ['sometimes', 'required_with:before_measured_at', 'integer', 'min:1'],
        ];
    }
}
