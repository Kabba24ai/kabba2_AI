<?php

namespace App\Http\Requests\Admin\Hrm\Roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        // You can sanitize here if needed
        //$this->merge(PurifyHelper::purify($this->all()));
    }

    public function rules(): array
    {
        return [
            'role_name' => ['required'],
            'color' => ['required'],
            'description' => ['required'],
            
        ];
    }

    public function messages(): array
    {
        return [
        ];
    }
}
