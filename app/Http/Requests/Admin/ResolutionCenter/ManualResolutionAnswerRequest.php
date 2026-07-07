<?php

namespace App\Http\Requests\Admin\ResolutionCenter;

use App\Helpers\PurifyHelper;
use App\Services\ResolutionCenter\ManualResolutionScenario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManualResolutionAnswerRequest extends FormRequest
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
            'issue_category' => ['required', Rule::in(array_keys(ManualResolutionScenario::CATEGORY_LABELS))],
            'selected_resolution' => ['required', Rule::in(array_keys(ManualResolutionScenario::RESOLUTION_LABELS))],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
