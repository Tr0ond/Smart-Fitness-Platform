<?php

namespace App\Http\Requests\Workout;

use Illuminate\Foundation\Http\FormRequest;

class ListWorkoutSessionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'before_id' => ['sometimes', 'integer', 'min:1'],
            'member_id' => ['prohibited'],
            'hoi_vien_id' => ['prohibited'],
        ];
    }
}
