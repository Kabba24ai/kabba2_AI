<?php

namespace App\Http\Requests\Admin\Crm\MessageManagement\EmailCategory;

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
        // Sanitize fields before validation
        $this->merge(
            PurifyHelper::purify($this->only([
                'email_cat_name',
                'email_cat_description'
            ]))
        );
    }

    public function rules(): array
    {
        return [
            'email_cat_name' => ['required'],
            'email_cat_description' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'email_cat_name.required' => 'Category name is required.',
        ];
    }
}
