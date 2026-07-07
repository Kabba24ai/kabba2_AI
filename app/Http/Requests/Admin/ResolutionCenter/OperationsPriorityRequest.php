<?php

namespace App\Http\Requests\Admin\ResolutionCenter;

use App\Services\ResolutionCenterService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OperationsPriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'priority' => ['required', Rule::in([
                ResolutionCenterService::PRIORITY_LOW,
                ResolutionCenterService::PRIORITY_NORMAL,
                ResolutionCenterService::PRIORITY_HIGH,
                ResolutionCenterService::PRIORITY_URGENT,
            ])],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
