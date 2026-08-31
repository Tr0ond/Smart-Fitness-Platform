<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminPaymentEventsRequest extends FormRequest
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
            'processing_status' => ['sometimes', 'string', Rule::in(['CHO_XU_LY', 'DA_XU_LY', 'BI_TU_CHOI', 'CAN_DOI_SOAT', 'CHO_THU_LAI'])],
            'provider_order_code' => ['sometimes', 'integer', 'min:1', 'max:9007199254740991'],
            'provider_reference' => ['sometimes', 'string', 'max:150'],
            'reconciliation_required' => ['sometimes', Rule::in([true, false, 1, 0, '1', '0', 'true', 'false'])],
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:from'],
            'sort_by' => ['sometimes', 'string', Rule::in(['received_at', 'processed_at', 'created_at'])],
            'sort_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('processing_status'))) {
            $this->merge(['processing_status' => strtoupper(trim($this->input('processing_status')))]);
        }
        if (is_string($this->input('sort_direction'))) {
            $this->merge(['sort_direction' => strtolower(trim($this->input('sort_direction')))]);
        }
    }
}
