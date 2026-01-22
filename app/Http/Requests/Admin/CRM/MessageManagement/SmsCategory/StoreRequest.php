<?php

namespace App\Http\Requests\Admin\Crm\MessageManagement\SmsCategory;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\PurifyHelper;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Sanitize input fields
        $this->merge(
            PurifyHelper::purify($this->only([
                'sms_cat_name',
                'sms_cat_description'
            ]))
        );
    }

    public function rules(): array
    {
        return [
            'sms_cat_name' => ['required', 'string', 'max:255'],
            'sms_cat_description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'sms_cat_name.required' => 'SMS category name is required.',
        ];
    }
}
