<?php

namespace App\Http\Requests\Admin\MaintenanceManagement\Suppliers\Tag;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Please enter a tag name.',
            'name.unique' => 'This tag name already exists. Please choose another.',
        ];
    }
}
