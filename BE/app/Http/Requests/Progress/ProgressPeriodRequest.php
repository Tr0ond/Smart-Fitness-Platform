<?php

namespace App\Http\Requests\Progress;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProgressPeriodRequest extends FormRequest
{
    public const MAX_PERIOD_DAYS = 366;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'from' => ['required_with:to', 'date_format:Y-m-d'],
            'to' => ['required_with:from', 'date_format:Y-m-d'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('from') || ! $this->filled('to')) {
                return;
            }

            $tu = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->input('from'), 'UTC');
            $den = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->input('to'), 'UTC');
            if ($tu === false || $den === false || $den->lessThan($tu)
                || $tu->diffInDays($den) + 1 > self::MAX_PERIOD_DAYS) {
                $validator->errors()->add('to', 'Khoảng Progress phải theo thứ tự và không quá 366 ngày.');
            }
        });
    }
}
