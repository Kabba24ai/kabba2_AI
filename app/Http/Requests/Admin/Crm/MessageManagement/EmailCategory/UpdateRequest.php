<?php

namespace App\Http\Requests\Admin\Crm\MessageManagement\EmailCategory;

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
            'email_cat_name' => 'required',
            'email_cat_description' => 'nullable',
        ];
    }
}
