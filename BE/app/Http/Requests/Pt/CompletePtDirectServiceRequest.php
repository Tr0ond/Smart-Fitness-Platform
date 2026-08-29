<?php

namespace App\Http\Requests\Pt;

use Illuminate\Foundation\Http\FormRequest;

class CompletePtDirectServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'assignment_id' => ['required', 'integer', 'min:1'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'member_id' => ['prohibited'],
            'trainer_id' => ['prohibited'],
            'pt_id' => ['prohibited'],
            'usage_id' => ['prohibited'],
            'membership_term_id' => ['prohibited'],
            'status' => ['prohibited'],
            'completed_at' => ['prohibited'],
            'confirmed_at' => ['prohibited'],
            'quota' => ['prohibited'],
            'used_counter' => ['prohibited'],
            'created_at' => ['prohibited'],
            'hoan_thanh_luc' => ['prohibited'],
            'xac_nhan_luc' => ['prohibited'],
            'ngay_bat_dau' => ['prohibited'],
            'ngay_ket_thuc' => ['prohibited'],
            '_idempotency_key' => ['required', 'uuid'],
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
