<?php

namespace App\Http\Requests\Workout;

use Illuminate\Foundation\Http\FormRequest;

class CompleteWorkoutSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
            '_idempotency_key' => ['required', 'uuid'],
            'member_id' => ['prohibited'],
            'hoi_vien_id' => ['prohibited'],
            'status' => ['prohibited'],
            'ended_at' => ['prohibited'],
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
