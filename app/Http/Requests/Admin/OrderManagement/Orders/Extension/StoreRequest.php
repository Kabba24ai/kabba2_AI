<?php

namespace App\Http\Requests\Admin\OrderManagement\Orders\Extension;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description'        => ['required', 'string', 'max:255'],
            'base_amount'        => ['required', 'numeric', 'min:0.01'],
            'add_tax'            => ['required', 'boolean'],
            // Optional pre-tax Store Credit discount applied at creation time.
            'store_credit_discount' => ['nullable', 'numeric', 'min:0.01'],
            'responsible_person' => ['required', 'exists:users,id'],
            'notes'              => ['nullable', 'string', 'max:1000'],
            // Duplicate-click guard key generated per modal open
            'request_uuid'       => ['nullable', 'string', 'max:64'],
        ];
    }
}
