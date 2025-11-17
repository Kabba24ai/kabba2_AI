<?php

namespace App\Http\Requests\Admin\Configurations\TermsConditions;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\PurifyHelper;

class SaveRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $input = PurifyHelper::purify($this->all());
        $this->merge($input);
    }

    public function rules()
    {
        return [
            'terms.terms_conditions_title' => 'required|string|max:255',
            'terms.terms_conditions_status' => 'required|string|in:Published,Draft,Pending',
            'terms.terms_conditions_description' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'terms.terms_conditions_title.required' => 'The title is required.',
            'terms.terms_conditions_status.required' => 'The status is required.',
            'terms.terms_conditions_status.in' => 'Invalid status selected.',
            'terms.terms_conditions_description.required' => 'The description cannot be empty.',
        ];
    }
}
