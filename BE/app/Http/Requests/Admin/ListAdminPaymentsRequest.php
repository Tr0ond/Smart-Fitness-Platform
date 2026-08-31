<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminPaymentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'order_code' => ['sometimes', 'string', 'max:40'],
            'member' => ['sometimes', 'string', 'max:254'],
            'payment_status' => ['sometimes', 'string', Rule::in(['DANG_TAO', 'CHO_THANH_TOAN', 'THANH_CONG', 'THAT_BAI', 'HUY', 'HET_HAN', 'CAN_DOI_SOAT'])],
            'order_status' => ['sometimes', 'string', Rule::in(['CHO_THANH_TOAN', 'DA_THANH_TOAN', 'HET_HAN', 'HUY', 'CAN_DOI_SOAT'])],
            'provider_order_code' => ['sometimes', 'integer', 'min:1', 'max:9007199254740991'],
            'provider_reference' => ['sometimes', 'string', 'max:150'],
            'reconciliation_required' => ['sometimes', Rule::in([true, false, 1, 0, '1', '0', 'true', 'false'])],
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:from'],
            'sort_by' => ['sometimes', 'string', Rule::in(['created_at', 'confirmed_at', 'expected_amount', 'received_amount'])],
            'sort_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['payment_status', 'order_status'] as $truong) {
            if (is_string($this->input($truong))) {
                $this->merge([$truong => strtoupper(trim($this->input($truong)))]);
            }
        }
        if (is_string($this->input('sort_direction'))) {
            $this->merge(['sort_direction' => strtolower(trim($this->input('sort_direction')))]);
        }
    }
}
