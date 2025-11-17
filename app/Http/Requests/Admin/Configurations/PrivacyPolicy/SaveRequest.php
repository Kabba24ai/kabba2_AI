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
            'privacy.privacy_policy_title' => 'required|string|max:255',
            'privacy.privacy_policy_status' => 'required|string|in:Published,Draft,Pending',
            'privacy.privacy_policy_description' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'privacy.privacy_policy_title.required' => 'The title is required.',
            'privacy.privacy_policy_status.required' => 'The status is required.',
            'privacy.privacy_policy_status.in' => 'Invalid status selected.',
            'privacy.privacy_policy_description.required' => 'The description cannot be empty.',
        ];
    }
}
