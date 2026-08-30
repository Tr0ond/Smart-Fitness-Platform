<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class ApplyAiProposalRequest extends FormRequest
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
            'member_id' => ['prohibited'],
            'hoi_vien_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'nguoi_dung_id' => ['prohibited'],
            'proposal_id' => ['prohibited'],
            'request_id' => ['prohibited'],
            'plan_id' => ['prohibited'],
            'version_id' => ['prohibited'],
            'base_version_id' => ['prohibited'],
            'status' => ['prohibited'],
            'change_type' => ['prohibited'],
            'effective_date' => ['prohibited'],
            'content' => ['prohibited'],
            'proposal' => ['prohibited'],
            'structured_output' => ['prohibited'],
            'candidate_ids' => ['prohibited'],
            'quota' => ['prohibited'],
            'membership_term_id' => ['prohibited'],
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

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            '_idempotency_key.required' => 'Idempotency-Key là bắt buộc.',
            '_idempotency_key.uuid' => 'Idempotency-Key phải là UUID hợp lệ.',
        ];
    }
}
