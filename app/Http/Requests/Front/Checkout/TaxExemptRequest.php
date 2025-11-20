<?php

namespace App\Http\Requests\Front\Checkout;

use App\Helpers\PurifyHelper;
use App\Http\Requests\ApiBaseFormRequest;

class TaxExemptRequest extends ApiBaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            // Ensure the passcode is required and numeric
            'is_tax_exempt' => 'required|boolean',
            'passcode' => 'required_if:is_tax_exempt,true|nullable|numeric',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'is_tax_exempt.required' => 'Tax exempt status is required.',
            'is_tax_exempt.boolean' => 'Tax exempt status must be true or false.',
            'passcode.required_if' => 'Admin code is required.',
            'passcode.numeric' => 'Admin code must be a numeric value.',
        ];
    }
}
