<?php

namespace App\Http\Requests\Admin\Crm\Customers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Helpers\PurifyHelper;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $input = PurifyHelper::purify($this->all(), ['company_name', 'first_name', 'last_name']);
        $this->merge($input);
    }

    public function rules(): array
    {

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email'
            ],
            'phone' => ['required', 'string', 'max:30'],

            'tax_document_review_status' => ['nullable'],

            'status' => ['required', Rule::in(['Active', 'Inactive', 'Archived'])],
            'is_guest' => ['boolean'],
            'tax_status' => ['required', Rule::in(['Taxable', 'Exempt'])],
            'tax_document_media_id' => ['nullable'],
            'tax_document_upload_date' => ['nullable'],
            'tax_document_valid_until' => ['nullable'],


            'account_approved_by' => ['nullable'],
            'tax_status_approved_by' => ['nullable'],



            'is_credit_account' => ['boolean'],
            'credit_limit' => ['nullable'],

            'company_website' => ['nullable'],
            'company_phone' => ['nullable'],

            'billing_address' => ['nullable', 'string'],
            'account_application_completed' => ['nullable'],

            'alladdresslist'=>['nullable'],
            'website_protocol' => ['nullable', 'string'],
            'website_extension' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'The selected status is invalid.',
            'tax_status.in' => 'The selected tax status is invalid.',
        ];
    }
}
