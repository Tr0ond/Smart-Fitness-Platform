<?php

namespace App\Http\Requests\Pt;

use Illuminate\Foundation\Http\FormRequest;

class CreatePtProposalRequest extends FormRequest
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
            'change_type' => ['required', 'in:TAO_MOI,DIEU_CHINH,THAY_BAI'],
            'title' => ['required', 'string', 'max:200'],
            'explanation' => ['required', 'string', 'max:10000'],
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'plan' => ['required', 'array:name,goal,template_id,days'],
            'plan.name' => ['required', 'string', 'max:150'],
            'plan.goal' => ['required', 'string', 'max:100'],
            'plan.template_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'plan.days' => ['required', 'array', 'min:1', 'max:7'],
            'plan.days.*' => ['required', 'array:order,weekday,name,estimated_minutes,exercises'],
            'plan.days.*.order' => ['required', 'integer', 'min:1', 'max:7'],
            'plan.days.*.weekday' => ['required', 'integer', 'between:2,8'],
            'plan.days.*.name' => ['required', 'string', 'max:150'],
            'plan.days.*.estimated_minutes' => ['required', 'integer', 'between:1,1440'],
            'plan.days.*.exercises' => ['required', 'array', 'min:1', 'max:50'],
            'plan.days.*.exercises.*' => ['required', 'array:exercise_id,order,target_sets,min_reps,max_reps,target_weight_kg,rest_seconds,notes'],
            'plan.days.*.exercises.*.exercise_id' => ['required', 'integer', 'min:1'],
            'plan.days.*.exercises.*.order' => ['required', 'integer', 'min:1', 'max:50'],
            'plan.days.*.exercises.*.target_sets' => ['required', 'integer', 'between:1,100'],
            'plan.days.*.exercises.*.min_reps' => ['required', 'integer', 'between:1,1000'],
            'plan.days.*.exercises.*.max_reps' => ['required', 'integer', 'between:1,1000', 'gte:plan.days.*.exercises.*.min_reps'],
            'plan.days.*.exercises.*.target_weight_kg' => ['sometimes', 'nullable', 'numeric', 'between:0,9999.99'],
            'plan.days.*.exercises.*.rest_seconds' => ['required', 'integer', 'between:0,86400'],
            'plan.days.*.exercises.*.notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'member_id' => ['prohibited'],
            'trainer_id' => ['prohibited'],
            'assignment_id' => ['prohibited'],
            'creator_id' => ['prohibited'],
            'base_plan_id' => ['prohibited'],
            'base_version_id' => ['prohibited'],
            'profile_version' => ['prohibited'],
            'plan_change_marker' => ['prohibited'],
            'status' => ['prohibited'],
            'expires_at' => ['prohibited'],
            'content_hash' => ['prohibited'],
            'structure_version' => ['prohibited'],
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
