<?php

namespace App\Http\Requests\Progress;

class ExerciseProgressRequest extends ProgressPeriodRequest
{
    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'limit' => ['sometimes', 'integer', 'between:1,100'],
        ]);
    }
}
