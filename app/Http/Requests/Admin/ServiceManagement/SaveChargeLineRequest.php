<?php

namespace App\Http\Requests\Admin\ServiceManagement;

use App\Enums\Service\ServiceChargeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveChargeLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'charge_type' => ['required', Rule::enum(ServiceChargeType::class)],
            'description' => ['required', 'string', 'max:500'],
            'quantity'    => ['nullable', 'numeric', 'min:0.01', 'max:99999'],
            'unit_amount' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'taxable'     => ['nullable', 'boolean'],
            'billable'    => ['nullable', 'boolean'],
        ];
    }
}
