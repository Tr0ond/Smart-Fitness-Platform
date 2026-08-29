<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class CreateAiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'request_type' => ['required', 'string', 'in:TAO_KE_HOACH,DIEU_CHINH,THAY_BAI'],
            'prompt' => ['required', 'string', 'max:2000'],
            'member_id' => ['prohibited'],
            'hoi_vien_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'nguoi_dung_id' => ['prohibited'],
            'candidate_ids' => ['prohibited'],
            'proposal' => ['prohibited'],
            'structured_output' => ['prohibited'],
            'quota' => ['prohibited'],
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

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'prompt.required' => 'Nội dung yêu cầu AI không được để trống.',
            'request_type.in' => 'Loại yêu cầu chưa được hỗ trợ.',
            '_idempotency_key.required' => 'Idempotency-Key là bắt buộc.',
            '_idempotency_key.uuid' => 'Idempotency-Key phải là UUID hợp lệ.',
        ];
    }
}
