<?php

namespace App\Http\Requests\Admin\Configurations\ApplicationCodeSettings;

use App\Helpers\PurifyHelper;
use Illuminate\Foundation\Http\FormRequest;

class SaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge(
            PurifyHelper::purify($this->all())
        );
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'application_code' => [
                'required',
                'string',
                'size:6',
                'regex:/^[A-Za-z0-9]+$/',
            ],
        ];
    }

    /**
     * Custom messages
     */
    public function messages(): array
    {
        return [
            'application_code.required' => 'Application Code is required.',
            'application_code.size' => 'Application Code must be exactly 6 characters.',
            'application_code.regex' => 'Only letters and numbers are allowed. Special characters are not allowed.',
        ];
    }
}