<?php

namespace App\Http\Requests\Admin\Crm\Customers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Helpers\PurifyHelper;

class ViewUpdateRequest extends FormRequest
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
            'first_name' => ['nullable'],
            'last_name' => ['nullable'],
            'company_name' => ['nullable'],
            'email' => [
                'nullable',
                'email'
            ],

            'phone' => ['nullable', 'string', 'max:30'],

            'company_website' => ['nullable'],
            'company_phone' => ['nullable'],
            'tax_document_type'=> ['nullable'],
            'is_credit_account' => ['boolean'],
            'tax_document_upload_date' => ['nullable'],
            'tax_document_valid_until' => ['nullable'],
            'alladdresslist'=>['nullable'],
            'tax_document_media_id' => ['nullable'],
            'tax_status' => ['nullable'] ,
            'credit_limit' =>['nullable'],

            'tags' => ['nullable'],
            'sameAsBilling' => ['nullable'],

            'website_protocol' => ['nullable'],
            'website_extension' => ['nullable'],

        ];
    }

    public function messages(): array
    {
        return [

        ];
    }
}