<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePackageBenefitsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'gym_access' => ['required', 'boolean'],
            'fitness_assistant' => ['required', 'boolean'],
            'fitness_assistant_limit' => ['present', 'nullable', 'integer', 'min:0', 'max:4294967295'],
            'trainer_chat' => ['required', 'boolean'],
            'direct_trainer_sessions' => ['required', 'integer', 'min:0', 'max:65535'],
            'package_id' => ['prohibited'],
            'goi_tap_id' => ['prohibited'],
            'configuration_version' => ['prohibited'],
            'phien_ban_cau_hinh' => ['prohibited'],
        ];
    }
}
