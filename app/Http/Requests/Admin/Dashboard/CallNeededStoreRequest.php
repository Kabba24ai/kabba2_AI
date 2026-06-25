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
            'nullable',
            'exists:customers,id',
        ],
            'supplier_id' => [
                'nullable',
                'exists:suppliers,id',
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

            'priority' => [
                'nullable',
                'string',
                'in:low,normal,high,urgent',
            ],
            'contact_name'  => 'required_without_all:customer_id,supplier_id|nullable|string|max:255',
            'contact_phone' => 'required_without_all:customer_id,supplier_id|nullable',
            'contact_email' => 'nullable|email',
            'customer_id.exists' =>
            'Selected customer is invalid.',
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