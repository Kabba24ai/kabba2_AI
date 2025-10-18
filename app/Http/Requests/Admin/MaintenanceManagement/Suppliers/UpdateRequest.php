<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\Suppliers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Adjust authorization logic as needed
    }

    public function rules()
    {
        return [
            // Company Information
            'supplierCompany' => 'required',
            'supplierEmail'   => 'nullable|email',
            'supplierPhone'   => [
                'nullable'
            ],
            'supplierWebsite' => 'nullable|url',
            'supplierAddress' => 'nullable|string',
            'supplierCity'    => 'nullable|string',
            'supplierState'   => 'nullable|string',
            'supplierZip'     => 'nullable|string',
            'supplierCountry' => 'nullable|string',
            'supplierTax'     => 'nullable|string',

            // Supplier category & status
            'supplierCategory'      => 'nullable',
            'supplierStatus'        => 'required|string|in:Active,Inactive,Pending',
            'supplierPaymentTerms'  => 'nullable',

            // Tags (array)
            'tags' => 'nullable|array',
            'tags.*' => 'integer',

            // Primary Contact
            'primaryContactName'  => 'nullable',
            'primaryContactEmail' => 'nullable|email',
            'primaryContactPhone' => [
                'nullable'
            ],

            'upload_company_logo' => 'nullable|file|mimes:jpg,jpeg,png|max:10240',

            // Secondary Contact
            'secondaryContactName'  => 'nullable',
            'secondaryContactEmail' => 'nullable|email',
            'secondaryContactPhone' => [
                'nullable'
            ],
        ];
    }

    public function messages()
    {
        return [
            'supplierCompany.required' => 'Company Name is required.',
            'supplierEmail.email' => 'Please enter a valid email address.',
            'supplierWebsite.url' => 'Please enter a valid website URL.',
            'supplierPhone.regex' => 'Phone number must be in format (xxx) xxx-xxxx.',
            'primaryContactPhone.regex' => 'Primary contact phone must be in format (xxx) xxx-xxxx.',
            'secondaryContactPhone.regex' => 'Secondary contact phone must be in format (xxx) xxx-xxxx.',
            'supplierStatus.required' => 'Status is required.',
        ];
    }
}
