<?php

namespace App\Http\Requests\Admin\ResolutionCenter;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class OperationsEscalateRequest extends FormRequest
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
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
