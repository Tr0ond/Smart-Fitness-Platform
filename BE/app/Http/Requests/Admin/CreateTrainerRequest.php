<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTrainerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:150'],
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'phone' => ['nullable', 'string', 'max:20'],
            'introduction' => ['nullable', 'string', 'max:16383'],
            'specialties' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in(['HOAT_DONG', 'NGUNG_NHAN_PHAN_CONG'])],
            '_idempotency_key' => ['required', 'uuid'],
            'trainer_code' => ['prohibited'],
            'role' => ['prohibited'],
            'roles' => ['prohibited'],
            'account_id' => ['prohibited'],
            'nguoi_dung_id' => ['prohibited'],
            'password' => ['prohibited'],
            'mat_khau_bam' => ['prohibited'],
        ];
    }

    public function idempotencyKey(): string
    {
        return trim((string) $this->header('Idempotency-Key'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            '_idempotency_key' => $this->idempotencyKey(),
            'status' => is_string($this->input('status')) ? strtoupper(trim($this->input('status'))) : $this->input('status'),
        ]);
    }
}
