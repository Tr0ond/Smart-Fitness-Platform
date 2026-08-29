<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'price' => ['prohibited'],
            'amount' => ['prohibited'],
            'so_tien' => ['prohibited'],
            'hoi_vien_id' => ['prohibited'],
            'member_id' => ['prohibited'],
            'duration' => ['prohibited'],
            'thoi_han_ngay' => ['prohibited'],
            'benefits' => ['prohibited'],
            'quyen_loi' => ['prohibited'],
            'payment_status' => ['prohibited'],
            'membership_status' => ['prohibited'],
            'return_url' => ['prohibited'],
            'cancel_url' => ['prohibited'],
            'order_code' => ['prohibited'],
        ];
    }

    /** Đưa khóa chống lặp từ HTTP header vào cùng pipeline validation của Laravel. */
    protected function prepareForValidation(): void
    {
        $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
    }
}
