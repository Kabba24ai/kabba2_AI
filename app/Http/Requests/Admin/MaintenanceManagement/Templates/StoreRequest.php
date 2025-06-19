<?php

namespace App\Http\Requests\Maintenance\Admin\Templates;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:Bulldozers,Compressors,Excavators,Generators,Loaders,Supplies',
            'description' => 'nullable|string',
            'parts' => 'required|array|min:1',
            'parts.*' => 'exists:parts,id'
        ];
    }

    public function messages()
    {
        return [
            'parts.required' => 'At least one part must be added to the template.',
            'parts.min' => 'At least one part must be added to the template.',
        ];
    }
}
