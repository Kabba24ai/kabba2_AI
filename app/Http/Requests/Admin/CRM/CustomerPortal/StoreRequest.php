<?php

namespace App\Http\Requests\Admin\Crm\CustomerPortal;
use Illuminate\Validation\Rule;
use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;


class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge(PurifyHelper::purify($this->all(),['content']));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:255'],
            // 'email' => [
            //     'nullable',
            //     'email'
            // ],
            // 'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:customers,phone'],
            'dob' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['Active', 'Inactive', 'Archived'])],
            'is_guest' => ['boolean'],
            'tax_status' => ['required', Rule::in(['Taxable', 'Exempt'])],
            'tax_document_media_id' => ['nullable'],
            'tax_document_upload_date' => ['nullable', 'date'],
            'tax_document_valid_until' => ['nullable', 'date'],
            'is_credit_account' => ['boolean'],
            'credit_limit' => ['nullable'],
            'website' => ['nullable', 'string', 'max:255'],
            'billing_address' => ['nullable', 'string'],
            'account_application_completed' => ['nullable', 'date'],
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
