<?php

namespace App\Http\Requests\Admin\ServiceManagement;

use Illuminate\Foundation\Http\FormRequest;

class SavePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'part_number'       => ['nullable', 'string', 'max:100'],
            'description'       => ['required', 'string', 'max:500'],
            'quantity'          => ['nullable', 'numeric', 'min:0.01', 'max:99999'],
            'unit_cost'         => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'customer_price'    => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'warranty_eligible' => ['nullable', 'boolean'],
            'billable'          => ['nullable', 'boolean'],
        ];
    }
}
