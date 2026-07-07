<?php

namespace App\Http\Requests\Admin\ResolutionCenter;

use App\Helpers\PurifyHelper;
use App\Services\ResolutionCenter\CancellationRefundScenario;
use App\Services\ResolutionCenter\ResolutionScenarioRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(PurifyHelper::purify($this->all(), ['content']));

        // Phase 3.5: defaults to the reference scenario so the existing
        // "Start Resolution Case" form (which never sends this field) is
        // unaffected.
        if (! $this->filled('scenario_key')) {
            $this->merge(['scenario_key' => CancellationRefundScenario::KEY]);
        }
    }

    public function rules(): array
    {
        return [
            'issue' => ['required', 'string', 'max:2000'],
            'scenario_key' => ['required', Rule::in(array_keys(ResolutionScenarioRegistry::all()))],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
