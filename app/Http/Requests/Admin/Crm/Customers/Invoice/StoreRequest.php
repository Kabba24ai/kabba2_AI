<?php

namespace App\Http\Requests\Admin\Crm\Customers\Invoice;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\PurifyHelper;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true; // Add role/permission checks if needed
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Clean all input fields using PurifyHelper
        $cleaned = PurifyHelper::purify($this->all(), ['content']);

        // Merge back into the request
        $this->merge($cleaned);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'invoice_number'    => ['nullable', 'string', 'max:50'],
            'invoice_date'      => ['required', 'date'],
            'due_date'          => ['nullable', 'date'],
            'customer_id'       => ['required', 'exists:customers,id'],
            'subtotal'          => ['nullable', 'numeric', 'min:0'],
            'tax'               => ['nullable', 'numeric', 'min:0'],
            'total'             => ['required', 'numeric', 'min:0'],
            'invoice_notes'     => ['nullable', 'string'],
            'invoice_data'      => ['required', 'json'], 
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer for this invoice.',
            'customer_id.exists'   => 'Selected customer does not exist.',
            'invoice_date.required' => 'Invoice date is required.',
            'total.required'       => 'Invoice total is required.',
            'invoice_data.required' => 'Invoice items data is required.',
            'invoice_data.json'    => 'Invoice items must be a valid JSON string.',
        ];
    }
}
