<?php

namespace App\Http\Requests\Admin\ResolutionCenter;

use App\Helpers\PurifyHelper;
use App\Services\ResolutionCenterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(PurifyHelper::purify($this->all(), ['content']));
    }

    public function rules(): array
    {
        return [
            'employee_decision' => ['required', Rule::in([
                ResolutionCenterService::DECISION_FOLLOWED,
                ResolutionCenterService::DECISION_OVERRIDDEN,
            ])],
            'employee_decision_detail' => ['nullable', 'string', 'max:2000'],
            'manager_override_reason' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'outcome' => ['nullable', Rule::in([
                ResolutionCenterService::OUTCOME_PENDING,
                ResolutionCenterService::OUTCOME_COMPLETED,
                ResolutionCenterService::OUTCOME_CANCELLED,
            ])],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
