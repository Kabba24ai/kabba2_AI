<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\Suppliers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'status' => 'required|in:Active,Inactive',
            'street_address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:2',
            'zip_code' => 'nullable|string|max:10',
            'tax_id' => 'nullable|string|max:255',
            'main_phone' => 'nullable|string|max:255',
            'main_email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'sales_name' => 'nullable|string|max:255',
            'sales_phone' => 'nullable|string|max:255',
            'sales_cell' => 'nullable|string|max:255',
            'sales_email' => 'nullable|email|max:255',
            'inside_sales_name' => 'nullable|string|max:255',
            'inside_sales_phone' => 'nullable|string|max:255',
            'inside_sales_cell' => 'nullable|string|max:255',
            'inside_sales_email' => 'nullable|email|max:255',
            'technical_name' => 'nullable|string|max:255',
            'technical_phone' => 'nullable|string|max:255',
            'technical_cell' => 'nullable|string|max:255',
            'technical_email' => 'nullable|email|max:255',
            'parts_name' => 'nullable|string|max:255',
            'parts_phone' => 'nullable|string|max:255',
            'parts_cell' => 'nullable|string|max:255',
            'parts_email' => 'nullable|email|max:255',
            'payment_terms' => 'required|string|max:255',
            'shipping_terms' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}
