<?php

namespace App\Http\Requests\Admin\Configurations\PrivacyPolicy;

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
            // Privacy Policy
            'privacy.privacy_policy_title'        => 'required|string|max:255',
            'privacy.privacy_policy_description'  => 'required|string',

        
        ];
    }

    public function messages()
    {
        return [
            // Privacy
            'privacy.privacy_policy_title.required'       => 'Privacy Policy title is required.',
            'privacy.privacy_policy_description.required' => 'Privacy Policy description is required.',

        ];
    }
}
