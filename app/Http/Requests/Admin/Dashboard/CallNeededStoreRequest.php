<?php

namespace App\Http\Requests\Admin\Dashboard;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class CallNeededStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge(
            PurifyHelper::purify($this->all(), ['notes'])
        );
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'exists:customers,id',
            ],
'assigned_to' => ['required', 'exists:users,id'],
            'reason' => [
                'required',
                'string',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'is_urgent' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists'   => 'Selected customer is invalid.',

            'reason.required'      => 'Please select a reason.',
        ];
    }
}