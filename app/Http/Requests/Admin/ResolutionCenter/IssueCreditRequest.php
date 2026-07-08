<?php

namespace App\Http\Requests\Admin\ResolutionCenter;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class IssueCreditRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:2000'],
            // Phase 3.5: nullable here so Cancellation/Refund's existing
            // form (which never sends this field) is unaffected. Manual
            // Resolution requires it via its own form's `required`
            // attribute — see ManualResolutionScenario's business rules.
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
