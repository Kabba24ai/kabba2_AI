<?php

namespace App\Http\Requests\Admin\Crm\MessageManagement\SmsCategory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sms_cat_name' => ['required', 'string', 'max:255'],
            'sms_cat_description' => ['nullable', 'string'],
        ];
    }
}
