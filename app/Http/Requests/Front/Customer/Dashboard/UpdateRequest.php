<?php

namespace App\Http\Requests\Front\Customer\Dashboard;

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
            'unique_id' => ['required'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email'
            ],
            
            'phone' => ['required', 'string', 'max:30'],

            'company_website' => ['nullable'],
            'company_phone' => ['nullable'],
        
            'alladdresslist'=>['nullable'],

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
