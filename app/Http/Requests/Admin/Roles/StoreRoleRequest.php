<?php

namespace App\Http\Requests\Admin\Roles;

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
            
        ];
    }

    public function messages(): array
    {
        return [
        ];
    }
}
