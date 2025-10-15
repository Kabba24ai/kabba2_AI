<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\Suppliers;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Change to proper authorization if needed
    }

    public function rules()
    {
        return [
            // Company Information
            'company_name' => 'required',
            'email'        => 'nullable|email',
            'phone'        => 'nullable',
            'website'      => 'nullable|url',
            'address'      => 'nullable',
            'city'         => 'nullable',
            'state'        => 'nullable',
            'zip'          => 'nullable',
            'country'      => 'nullable',
            'tax_id'       => 'nullable',

            // Supplier category & status
            'category_id'  => 'nullable',
            'status'       => 'required',
            'payment_terms' => 'nullable',

            // Tags (array of tag IDs)
            'tags'         => 'nullable',

            // Primary Contact
            'primary_contact_name'  => 'nullable',
            'primary_contact_email' => 'nullable|email',
            'primary_contact_phone' => 'nullable',

            // Secondary Contact
            'secondary_contact_name'  => 'nullable',
            'secondary_contact_email' => 'nullable|email',
            'secondary_contact_phone' => 'nullable',

            // Additional notes
            'notes' => 'nullable',
        ];
    }
}
