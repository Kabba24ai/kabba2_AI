<?php

namespace App\Http\Requests\Admin\ServiceManagement;

use Illuminate\Foundation\Http\FormRequest;

class SaveDiagnosticRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warranty_possible'         => ['nullable', 'boolean'],
            'customer_damage_possible'  => ['nullable', 'boolean'],
            'recommended_repair'        => ['nullable', 'string', 'max:5000'],
            'estimated_labor_hours'     => ['nullable', 'numeric', 'min:0', 'max:999'],
            'estimated_parts_total'     => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'estimated_repair_total'    => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'diagnostic_fee_required'   => ['nullable', 'boolean'],
            'diagnostic_fee_amount'     => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'diagnostic_fee_paid'       => ['nullable', 'boolean'],
            'diagnostic_fee_creditable' => ['nullable', 'boolean'],
            'diagnostic_fee_credited'   => ['nullable', 'boolean'],
        ];
    }
}
