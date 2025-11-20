<?php


namespace App\Http\Requests\Admin\Hrm\Roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
       
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
