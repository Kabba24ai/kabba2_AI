<?php

namespace App\Http\Requests\Admin\Warranty;

use App\Enums\Warranty\WarrantyPath;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveWarrantyCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $external = $this->input('path') === WarrantyPath::External->value;

        return [
            // 1. Warranty path
            'path' => ['required', Rule::enum(WarrantyPath::class)],

            // Who owns it: internal cases link a fleet unit; external cases
            // link a CRM customer (their machine is not in our fleet).
            'equipment_id' => [Rule::requiredIf(!$external), 'nullable', 'integer', 'exists:equipment,id'],
            'customer_id'  => [Rule::requiredIf($external), 'nullable', 'integer', 'exists:customers,id'],

            // 2. Equipment identity
            'manufacturer'         => ['required', 'string', 'max:255'],
            'model'                => ['required', 'string', 'max:255'],
            'serial_number'        => ['required', 'string', 'max:255'],
            'engine_serial_number' => ['nullable', 'string', 'max:255'],
            'has_hour_meter'       => ['nullable', 'boolean'],
            'hours'                => [Rule::requiredIf($this->boolean('has_hour_meter')), 'nullable', 'integer', 'min:0'],

            // 3. Ownership & warranty information (optional)
            'purchase_date'                => ['nullable', 'date'],
            'selling_dealer'               => ['nullable', 'string', 'max:255'],
            'warranty_registration_number' => ['nullable', 'string', 'max:255'],

            // 4. Complaint
            'complaint'      => ['required', 'string', 'max:10000'],
            'internal_notes' => ['nullable', 'string', 'max:10000'],

            // 5. Diagnostic fee (external only — state recording, no payment)
            'diagnostic_fee_amount'    => [Rule::requiredIf($external), 'nullable', 'numeric', 'min:0', 'max:99999'],
            'diagnostic_fee_taxable'   => ['nullable', 'boolean'],
            'diagnostic_fee_collected' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'path'                  => 'warranty path',
            'equipment_id'          => 'equipment',
            'customer_id'           => 'customer',
            'diagnostic_fee_amount' => 'diagnostic fee',
            'hours'                 => 'hour meter reading',
        ];
    }
}
