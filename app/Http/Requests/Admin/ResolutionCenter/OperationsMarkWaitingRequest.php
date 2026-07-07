<?php

namespace App\Http\Requests\Admin\ResolutionCenter;

use App\Helpers\PurifyHelper;
use App\Services\ResolutionCenterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OperationsMarkWaitingRequest extends FormRequest
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
            'waiting_on' => ['required', Rule::in([
                ResolutionCenterService::WAITING_ON_CUSTOMER,
                ResolutionCenterService::WAITING_ON_EMPLOYEE,
            ])],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
