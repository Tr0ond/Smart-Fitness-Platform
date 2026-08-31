<?php

namespace App\Http\Requests\Progress;

use Illuminate\Foundation\Http\FormRequest;

class CreateBodyMeasurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $duLieu = [];
        if ($this->has('entry_id') && is_string($this->input('entry_id'))) {
            $duLieu['entry_id'] = strtolower(trim((string) $this->input('entry_id')));
        }
        if ($this->has('notes') && is_string($this->input('notes'))) {
            $ghiChu = trim((string) $this->input('notes'));
            $duLieu['notes'] = $ghiChu === '' ? null : $ghiChu;
        }
        if ($duLieu !== []) {
            $this->merge($duLieu);
        }
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'measured_at' => ['required', 'date'],
            'weight_kg' => ['required', 'numeric', 'gt:0', 'max:9999.99'],
            'height_cm' => ['required', 'numeric', 'gt:0', 'max:999.99'],
            'waist_cm' => ['sometimes', 'nullable', 'numeric', 'gt:0', 'max:999.99'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:500'],
            'entry_id' => ['required', 'uuid'],
            'member_id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'hoi_vien_id' => ['prohibited'],
            'nguoi_dung_id' => ['prohibited'],
            'bmi' => ['prohibited'],
            'created_at' => ['prohibited'],
            'ngay_tao' => ['prohibited'],
        ];
    }
}
