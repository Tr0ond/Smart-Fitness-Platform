<?php

namespace App\Http\Requests\Pt;

use Illuminate\Foundation\Http\FormRequest;

class RejectPtProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            '_idempotency_key' => ['required', 'uuid'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'member_id' => ['prohibited'],
            'proposal_id' => ['prohibited'],
            'plan_id' => ['prohibited'],
            'version_id' => ['prohibited'],
            'status' => ['prohibited'],
            'content' => ['prohibited'],
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
