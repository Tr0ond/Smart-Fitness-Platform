<?php

namespace App\Http\Requests\Workout;

use Illuminate\Foundation\Http\FormRequest;

class RecordWorkoutSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order' => ['required', 'integer', 'min:1', 'max:65535'],
            'reps' => ['required', 'integer', 'min:0', 'max:65535'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:99999.99', 'decimal:0,2'],
            'actual_rest_seconds' => ['nullable', 'integer', 'min:0', 'max:65535'],
            '_idempotency_key' => ['required', 'uuid'],
            'member_id' => ['prohibited'],
            'hoi_vien_id' => ['prohibited'],
            'exercise_id' => ['prohibited'],
            'completed_at' => ['prohibited'],
            'revision' => ['prohibited'],
        ];
    }

    public function idempotencyKey(): string
    {
        return trim((string) $this->header('Idempotency-Key'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['_idempotency_key' => $this->idempotencyKey()]);
    }
}
